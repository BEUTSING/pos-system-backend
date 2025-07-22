<?php

namespace App\Entity\Stock;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

   
    #[ORM\Column]
    private ?int $quantityst = null;

    #[ORM\Column]
    private ?int $Minimumstock = null;

    public function getId(): ?int
    {
        return $this->id;
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
