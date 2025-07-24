<?php

namespace App\Entity\Stock;

use App\Entity\Traits\ContactTrait;
use App\Repository\Stock\SupplierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Supplier
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
