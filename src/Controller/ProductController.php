<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductFilter;
use App\Form\ProductSearch;
use App\Repository\ProductRepository;
use App\Service\ElasticSearchIndexer;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ElasticSearchIndexer $elasticSearchIndexer,
    ) {
    }

    #[Route('/elastic_index', name: 'elastic_index')]
    public function elasticIndex(ProductRepository $productRepository): RedirectResponse
    {
        foreach ($productRepository->findAll() as $product) {
            $this->elasticSearchIndexer->indexProduct($product);
        }

        return $this->redirect($this->generateUrl('list_of_products'));
    }

    #[Route('/list_of_products_json', name: 'list_of_products_json', methods: ['GET'])]
    public function productsJson(Request $request, ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        $response = [];

        foreach ($products as $product) {
            $response = [
                'id' => $product->getId(),
                'name' => $product->getProductNameShort(),
            ];
        }

        return new Response(json_encode($response));
    }

    #[Route('/', name: 'list_of_products')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        // Search through products using filters
        $filterForm = $this->createForm(ProductFilter::class);
        $filterForm->handleRequest($request);

        $filters = [];

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters['product_filter'] = $filterForm->getData();
        }

        $queryBuilder = $productRepository->findByFilters($filters['product_filter'] ?? []);

        // Search through products using search query
        $searchForm = $this->createForm(ProductSearch::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $queryBuilder = $productRepository->searchByNameOrEan($searchForm->getData()['query']);
        }

        // Paginate
        $queryBuilder->setFirstResult($offset)->setMaxResults($limit);
        $paginator = new Paginator($queryBuilder->getQuery());
        $totalItems = \count($paginator);
        $totalPages = \ceil($totalItems / $limit);

        // View
        return $this->render('product/index.html.twig', [
            'products' => $paginator,
            'comparedProducts' => $request->getSession()->get('comparedProducts', []),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filterForm' => $filterForm->createView(),
            'searchForm' => $searchForm->createView(),
            'filters' => $filters,
        ]);
    }

    #[Route('/{id}/{slug}', name: 'product')]
    public function show(Product $product): Response
    {
        return $this->render('product/view.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/export/xls', name: 'export')]
    public function export(Product $product): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $product->getProductNameLong());

        $row = 2;

        foreach ($product->getProductAttributes() as $attribute) {
            $sheet->setCellValue('A' . $row, $attribute->getAttributeName());
            $sheet->setCellValue('B' . $row, $attribute->getAttributeValue());
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'product_' . $product->getId() . '.xlsx';

        $tempFilePath = tempnam(sys_get_temp_dir(), 'product_export_');
        $writer->save($tempFilePath);

        $fileContent = file_get_contents($tempFilePath);
        unlink($tempFilePath);

        $response = new Response($fileContent);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
