<?php

namespace App\Entity\Stock;

use App\Entity\Product\Product;
use App\Repository\Stock\PurchaseorderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PurchaseorderRepository::class)]
class Purchaseorder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $suppliername = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $orderdate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $duedate = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseorders')]
    private ?Product $productname = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSuppliername(): ?string
    {
        return $this->suppliername;
    }

    public function setSuppliername(string $suppliername): static
    {
        $this->suppliername = $suppliername;

        return $this;
    }

    public function getOrderdate(): ?\DateTime
    {
        return $this->orderdate;
    }

    public function setOrderdate(\DateTime $orderdate): static
    {
        $this->orderdate = $orderdate;

        return $this;
    }

    public function getDuedate(): ?\DateTime
    {
        return $this->duedate;
    }

    public function setDuedate(\DateTime $duedate): static
    {
        $this->duedate = $duedate;

        return $this;
    }

    public function getProductname(): ?Product
    {
        return $this->productname;
    }

    public function setProductname(?Product $productname): static
    {
        $this->productname = $productname;

        return $this;
    }
}
