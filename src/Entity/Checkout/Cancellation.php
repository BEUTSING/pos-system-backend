<?php

namespace App\Entity\Checkout;

use App\Entity\Security\User;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Checkout\CancellationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CancellationRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Cancellation
{   use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cancellationreason = null;

    #[ORM\OneToOne(inversedBy: 'cancellation', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sale $sale = null;

    #[ORM\ManyToOne(inversedBy: 'cancellations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCancellationreason(): ?string
    {
        return $this->cancellationreason;
    }

    public function setCancellationreason(string $cancellationreason): static
    {
        $this->cancellationreason = $cancellationreason;

        return $this;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(Sale $sale): static
    {
        $this->sale = $sale;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

   
}
