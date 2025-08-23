<?php

namespace App\Entity\Product;

use App\Entity\Stock\Purchaseorder;
use App\Entity\Stock\Stockmovement;
use App\Entity\Stock\Supplier;
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

    #[ORM\Column(length: 255, unique: true)]

    private ?string $productname = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $purchaseprice = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Stockmovement>
     */
    #[ORM\OneToMany(targetEntity: Stockmovement::class, mappedBy: 'product')]
    private Collection $stockmovements;

    
 
    /**
     * @var Collection<int, Purchaseorder>
     */
    #[ORM\OneToMany(targetEntity: Purchaseorder::class, mappedBy: 'productname')]
    private Collection $purchaseorders;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $minimumstock = null;

   
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $saleprice = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Supplier $supplier = null;
   

    public function __construct()
    {
        $this->stockmovements = new ArrayCollection();
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

    public function getMinimumstock(): ?int
    {
        return $this->minimumstock;
    }

    public function setMinimumstock(int $minimumstock): static
    {
        $this->minimumstock = $minimumstock;

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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }


}
