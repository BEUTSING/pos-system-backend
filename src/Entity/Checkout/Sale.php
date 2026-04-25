<?php

namespace App\Entity\Checkout;

use App\Entity\Security\User;
use App\Entity\Traits\CompanyTrait;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Checkout\SaleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Sale
{   
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $isPaid = null;

    #[ORM\OneToOne(inversedBy: 'sale', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\Column(length: 255)]
    private ?string $PaymentMethod = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teller = null;

    #[ORM\OneToOne(mappedBy: 'sale', cascade: ['persist', 'remove'])]
    private ?Cancellation $cancellation = null;

    #[ORM\Column(length: 50)]

    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(CustomerOrder $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->PaymentMethod;
    }

    public function setPaymentMethod(string $PaymentMethod): static
    {
        $this->PaymentMethod = $PaymentMethod;

        return $this;
    }

    public function getTeller(): ?User
    {
        return $this->teller;
    }

    public function setTeller(?User $teller): static
    {
        $this->teller = $teller;

        return $this;
    }

    public function getCancellation(): ?Cancellation
    {
        return $this->cancellation;
    }

    public function setCancellation(Cancellation $cancellation): static
    {
        // set the owning side of the relation if necessary
        if ($cancellation->getSale() !== $this) {
            $cancellation->setSale($this);
        }

        $this->cancellation = $cancellation;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
