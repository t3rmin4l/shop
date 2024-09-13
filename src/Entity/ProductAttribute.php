<?php

namespace App\Entity;

use App\Repository\ProductAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $attribute_name = null;

    #[ORM\Column(length: 2000)]
    private ?string $attribute_value = null;

    #[ORM\ManyToOne(inversedBy: 'productAttributes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttributeName(): ?string
    {
        return $this->attribute_name;
    }

    public function setAttributeName(string $attribute_name): static
    {
        $this->attribute_name = $attribute_name;

        return $this;
    }

    public function getAttributeValue(): ?string
    {
        return $this->attribute_value;
    }

    public function setAttributeValue(string $attribute_value): static
    {
        $this->attribute_value = $attribute_value;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
