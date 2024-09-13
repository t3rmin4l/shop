<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['slug', 'ledvance_product_id'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $ledvance_product_id = null;

    #[ORM\Column(length: 13)]
    private ?string $ean_number = null;

    #[ORM\Column(length: 255)]
    private ?string $product_name_short = null;

    #[ORM\Column(length: 255)]
    private ?string $product_name_long = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_url = null;

    /**
     * @var Collection<int, ProductAttribute>
     */
    #[ORM\OneToMany(targetEntity: ProductAttribute::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $productAttributes;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->productAttributes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLedvanceProductId(): ?int
    {
        return $this->ledvance_product_id;
    }

    public function setLedvanceProductId(int $ledvance_product_id): static
    {
        $this->ledvance_product_id = $ledvance_product_id;

        return $this;
    }

    public function getEanNumber(): ?string
    {
        return $this->ean_number;
    }

    public function setEanNumber(string $ean_number): static
    {
        $this->ean_number = $ean_number;

        return $this;
    }

    public function getProductNameShort(): ?string
    {
        return $this->product_name_short;
    }

    public function setProductNameShort(string $product_name_short): static
    {
        $this->product_name_short = $product_name_short;

        return $this;
    }

    public function getProductNameLong(): ?string
    {
        return $this->product_name_long;
    }

    public function setProductNameLong(string $product_name_long): static
    {
        $this->product_name_long = $product_name_long;
        $this->updateSlug();

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @return Collection<int, ProductAttribute>
     */
    public function getProductAttributes(): Collection
    {
        return $this->productAttributes;
    }

    public function addProductAttribute(ProductAttribute $productAttribute): static
    {
        if (!$this->productAttributes->contains($productAttribute)) {
            $this->productAttributes->add($productAttribute);
            $productAttribute->setProduct($this);
        }

        return $this;
    }

    public function removeProductAttribute(ProductAttribute $productAttribute): static
    {
        if ($this->productAttributes->removeElement($productAttribute)) {
            // set the owning side to null (unless already changed)
            if ($productAttribute->getProduct() === $this) {
                $productAttribute->setProduct(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    private function updateSlug(): void
    {
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug($this->product_name_long)->lower();
    }
}
