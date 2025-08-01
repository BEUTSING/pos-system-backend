<?php

namespace App\Entity\Checkout;

use App\Entity\Traits\ContactTrait;
use App\Repository\Checkout\CustomerRepository ;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass:CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Customer
{
    use ContactTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    
}
