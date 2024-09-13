<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Form\ProductImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportController extends AbstractController
{
    #[Route('/import', name: 'import')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        ini_set('memory_limit', '-1');

        $form = $this->createForm(ProductImport::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $jsonData = json_decode(file_get_contents($file->getPathname()), true);

            foreach ($jsonData as $productData) {
                if (!\is_null(
                    $em->getRepository(Product::class)->findOneBy([
                        'ledvance_product_id' => $productData['Ledvance Product ID'],
                    ])
                )) {
                    continue;
                }

                $product = new Product();
                $product
                    ->setLedvanceProductId($productData['Ledvance Product ID'])
                    ->setEanNumber($productData['EAN Nummer'])
                    ->setProductNameShort($productData['Product name short'])
                    ->setProductNameLong($productData['Product name long'])
                    ->setImageUrl($productData['Primary Image link'] ?? null);

                foreach ($productData as $attributeName => $attributeValue) {
                    if (\in_array($attributeName, [
                        'Ledvance Product ID',
                        'EAN Nummer',
                        'Product name short',
                        'Product name long',
                        'Primary Image link',
                    ])) {
                        continue;
                    }

                    $attribute = new ProductAttribute();
                    $attribute->setAttributeName($attributeName);
                    $attribute->setAttributeValue($attributeValue);

                    $product->addProductAttribute($attribute);
                }

                $em->persist($product);
            }

            $em->flush();

            $this->addFlash('success', 'Products successfully uploaded.');
            return $this->redirectToRoute('import');
        }

        return $this->render('import/index.html.twig', [
            'form' => $form,
        ]);
    }
}
