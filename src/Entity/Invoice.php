<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Customername = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?Product $Productname = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    private ?string $totaux = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?Sale $saledate = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?Customer $customernam = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $cashier = null;

   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomername(): ?string
    {
        return $this->Customername;
    }

    public function setCustomername(string $Customername): static
    {
        $this->Customername = $Customername;

        return $this;
    }

    public function getProductname(): ?Product
    {
        return $this->Productname;
    }

    public function setProductname(?Product $Productname): static
    {
        $this->Productname = $Productname;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getTotaux(): ?int
    {
        return $this->totaux;
    }

    public function setTotaux(int $totaux): static
    {
        $this->totaux = $totaux;

        return $this;
    }

    public function getSaledate(): ?Sale
    {
        return $this->saledate;
    }

    public function setSaledate(?Sale $saledate): static
    {
        $this->saledate = $saledate;

        return $this;
    }

    public function getCustomernam(): ?Customer
    {
        return $this->customernam;
    }

    public function setCustomernam(?Customer $customernam): static
    {
        $this->customernam = $customernam;

        return $this;
    }

    public function getCashier(): ?Users
    {
        return $this->cashier;
    }

    public function setCashier(?Users $cashier): static
    {
        $this->cashier = $cashier;

        return $this;
    }

   
}
