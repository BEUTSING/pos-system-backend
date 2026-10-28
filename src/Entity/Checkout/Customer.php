<?php

namespace App\Entity\Checkout;

use App\Entity\Traits\CompanyTrait;
use App\Entity\Traits\ContactTrait;
use App\Repository\Checkout\CustomerRepository ;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass:CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Customer
{   
    use CompanyTrait;
    use ContactTrait;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

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
