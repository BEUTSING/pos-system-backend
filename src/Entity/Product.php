<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $productname = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $purchaseprice = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Shelf $shelf = null;

    /**
     * @var Collection<int, Stockmovement>
     */
    #[ORM\OneToMany(targetEntity: Stockmovement::class, mappedBy: 'product')]
    private Collection $stockmovements;

    /**
     * @var Collection<int, Stock>
     */
    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'productst')]
    private Collection $stocks;

    /**
     * @var Collection<int, Sale>
     */
    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'produit')]
    private Collection $sales;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'Productname')]
    private Collection $invoices;

    /**
     * @var Collection<int, Purchaseorder>
     */
    #[ORM\OneToMany(targetEntity: Purchaseorder::class, mappedBy: 'productname')]
    private Collection $purchaseorders;

    public function __construct()
    {
        $this->stockmovements = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->purchaseorders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductname(): ?string
    {
        return $this->productname;
    }

    public function setProductname(string $productname): static
    {
        $this->productname = $productname;

        return $this;
    }

    public function getPurchaseprice(): ?string
    {
        return $this->purchaseprice;
    }

    public function setPurchaseprice(string $purchaseprice): static
    {
        $this->purchaseprice = $purchaseprice;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    public function setShelf(?Shelf $shelf): static
    {
        $this->shelf = $shelf;

        return $this;
    }

    /**
     * @return Collection<int, Stockmovement>
     */
    public function getStockmovements(): Collection
    {
        return $this->stockmovements;
    }

    public function addStockmovement(Stockmovement $stockmovement): static
    {
        if (!$this->stockmovements->contains($stockmovement)) {
            $this->stockmovements->add($stockmovement);
            $stockmovement->setProduct($this);
        }

        return $this;
    }

    public function removeStockmovement(Stockmovement $stockmovement): static
    {
        if ($this->stockmovements->removeElement($stockmovement)) {
            // set the owning side to null (unless already changed)
            if ($stockmovement->getProduct() === $this) {
                $stockmovement->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setProductst($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getProductst() === $this) {
                $stock->setProductst(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sale>
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): static
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setProduit($this);
        }

        return $this;
    }

    public function removeSale(Sale $sale): static
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getProduit() === $this) {
                $sale->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setProductname($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getProductname() === $this) {
                $invoice->setProductname(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Purchaseorder>
     */
    public function getPurchaseorders(): Collection
    {
        return $this->purchaseorders;
    }

    public function addPurchaseorder(Purchaseorder $purchaseorder): static
    {
        if (!$this->purchaseorders->contains($purchaseorder)) {
            $this->purchaseorders->add($purchaseorder);
            $purchaseorder->setProductname($this);
        }

        return $this;
    }

    public function removePurchaseorder(Purchaseorder $purchaseorder): static
    {
        if ($this->purchaseorders->removeElement($purchaseorder)) {
            // set the owning side to null (unless already changed)
            if ($purchaseorder->getProductname() === $this) {
                $purchaseorder->setProductname(null);
            }
        }

        return $this;
    }
}
