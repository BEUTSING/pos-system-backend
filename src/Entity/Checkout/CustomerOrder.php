<?php

namespace App\Entity\Checkout;

use App\Entity\Security\User;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Checkout\CustomerOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
#[ORM\Table(name: '`customer_order`')]
#[ORM\HasLifecycleCallbacks]
class CustomerOrder
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder', orphanRemoval: true)]
    private Collection $orderitems;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $waiter = null;

    #[ORM\OneToOne(mappedBy: 'customerOrder', cascade: ['persist', 'remove'])]
    private ?Sale $sale = null;

    public function __construct()
    {
        $this->orderitems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderitems;
    }

    public function addOrderItem(OrderItem $orderitem): static
    {
        if (!$this->orderitems->contains($orderitem)) {
            $this->orderitems->add($orderitem);
            $orderitem->setCustomerOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderitem): static
    {
        if ($this->orderitems->removeElement($orderitem)) {
            // set the owning side to null (unless already changed)
            if ($orderitem->getCustomerOrder() === $this) {
                $orderitem->setCustomerOrder(null);
            }
        }

        return $this;
    }

    public function getWaiter(): ?User
    {
        return $this->waiter;
    }

    public function setWaiter(?User $waiter): static
    {
        $this->waiter = $waiter;

        return $this;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(Sale $sale): static
    {
        // set the owning side of the relation if necessary
        if ($sale->getCustomerOrder() !== $this) {
            $sale->setCustomerOrder($this);
        }

        $this->sale = $sale;

        return $this;
    }
}
