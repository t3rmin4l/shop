<?php

declare(strict_types=1);

namespace App\Form;

use App\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilter extends AbstractType
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attributesToFilter = [
            'Wattage',
            'Dimmable',
            'Protection Type',
            'Light Type',
            'Type of Current',
            'Color Temperature',
            'Beam angle',
            'Luminous Flux',
            'Application Environment',
            'Mounting Location',
            'HousingType',
            'ConnectionType',
            'Luminaire Sensors',
        ];

        foreach ($attributesToFilter as $attribute) {
            $choices = $this->productRepository->findDistinctAttributeValues($attribute);

            $builder
                ->add(\str_replace(' ', '_', $attribute), ChoiceType::class, [
                    'choices' => \array_combine($choices, $choices),
                    'label' => $attribute,
                    'required' => false,
                    'attr' => ['class' => 'input mb-2 is-info is-small', 'placeholder' => $attribute],
                    'placeholder' => \sprintf('-- %s --', $attribute),
                ]);
        }

        $builder->add('filter', SubmitType::class, ['attr' => ['class' => 'button is-link']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
        ]);
    }
}
