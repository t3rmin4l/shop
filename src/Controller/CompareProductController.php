<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompareProductController extends AbstractController
{
    #[Route('/compare/list', name: 'compare')]
    public function index(Request $request, ProductRepository $pr): Response
    {
        return $this->render('product/compare.html.twig', [
            'products' => $pr->findBy(['id' => $request->getSession()->get('comparedProducts', [])]),
        ]);
    }

    #[Route('/{id}/compare/add', name: 'add_to_compare')]
    public function addToCompare(Product $product, Request $request): Response
    {
        $comparedProducts = $request->getSession()->get('comparedProducts', []);

        if (!\in_array($product->getId(), $comparedProducts, true)) {
            $comparedProducts[] = $product->getId();
        }

        $request->getSession()->set('comparedProducts', $comparedProducts);
        $this->addFlash('success', 'Added to compare.');

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('compare'));
    }

    #[Route('/{id}/compare/remove', name: 'remove_from_compare')]
    public function removeFromCompare(Product $product, Request $request): Response
    {
        $comparedProducts = \array_filter(
            $request->getSession()->get('comparedProducts', []),
            fn($p) => $p !== $product->getId(),
        );

        $request->getSession()->set('comparedProducts', $comparedProducts);
        $this->addFlash('success', 'Removed from compare.');

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('compare'));
    }

    #[Route('/compare/export/pdf', name: 'compare_export_pdf')]
    public function exportPdf(Request $request, ProductRepository $pr): Response
    {
        $dompdf = new Dompdf(new Options());

        $html = $this->renderView('product/compare_pdf.html.twig', [
            'products' => $pr->findBy(['id' => $request->getSession()->get('comparedProducts', [])]),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="comparison.pdf"',
        ]);
    }
}
