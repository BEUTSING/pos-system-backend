<?php

namespace App\Entity\Checkout;

use App\Entity\Media\Logo;
use App\Entity\Traits\CompanyTrait;
use App\Entity\Traits\ContactTrait;
use App\Repository\Checkout\CustomerRepository ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass:CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Customer
{   
    use CompanyTrait;
    use ContactTrait;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    /** Public path of the primary customer picture. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

    /**
     * @var Collection<int, Logo>
     */
    #[ORM\OneToMany(targetEntity: Logo::class, mappedBy: 'customer')]
    private Collection $logos;

    public function __construct()
    {
        $this->logos = new ArrayCollection();
    }

    /** @return Collection<int, Logo> */
    public function getLogos(): Collection
    {
        return $this->logos;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }
    
}
