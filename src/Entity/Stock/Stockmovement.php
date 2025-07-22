<?php

namespace App\Entity\Stock;

use App\Entity\Product\Product;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Stock\StockmovementRepository;

#[ORM\Entity(repositoryClass: StockmovementRepository::class)]
class Stockmovement
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stockmovements')]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(length: 50)]
    private ?string $typemovement = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $datemovement = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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

    public function getTypemovement(): ?string
    {
        return $this->typemovement;
    }

    public function setTypemovement(string $typemovement): static
    {
        $this->typemovement = $typemovement;

        return $this;
    }

    public function getDatemovement(): ?\DateTime
    {
        return $this->datemovement;
    }

    public function setDatemovement(\DateTime $datemovement): static
    {
        $this->datemovement = $datemovement;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

}
