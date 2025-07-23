<?php

namespace App\Entity\Product;

use App\Entity\Checkout\Invoice;
use App\Entity\Checkout\Sale;
use App\Entity\Checkout\Shelf;
use App\Entity\Stock\Purchaseorder;
use App\Entity\Stock\Stockmovement;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Product\ProductRepository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Product
{
    Use TimestampableTrait;
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

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $ninimumstock = null;

    #[ORM\Column(length: 255)]
    private ?string $supplier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $saleprice = null;

    public function __construct()
    {
        $this->stockmovements = new ArrayCollection();
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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getNinimumstock(): ?int
    {
        return $this->ninimumstock;
    }

    public function setNinimumstock(int $ninimumstock): static
    {
        $this->ninimumstock = $ninimumstock;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSaleprice (): ?string
    {
        return $this->saleprice;
    }

    public function setSaleprice(string $saleprice): static
    {
        $this->saleprice =$saleprice;

        return $this;
    }
}
