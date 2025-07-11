<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Product $productst = null;

    #[ORM\Column]
    private ?int $quantityst = null;

    #[ORM\Column]
    private ?int $Minimumstock = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductst(): ?Product
    {
        return $this->productst;
    }

    public function setProductst(?Product $productst): static
    {
        $this->productst = $productst;

        return $this;
    }

    public function getQuantityst(): ?int
    {
        return $this->quantityst;
    }

    public function setQuantityst(int $quantityst): static
    {
        $this->quantityst = $quantityst;

        return $this;
    }

    public function getMinimumstock(): ?int
    {
        return $this->Minimumstock;
    }

    public function setMinimumstock(int $Minimumstock): static
    {
        $this->Minimumstock = $Minimumstock;

        return $this;
    }
}
