<?php

namespace App\Entity\Media;

use App\Entity\Checkout\Customer;
use App\Entity\Company\Company;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Media\LogoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores uploaded image files for companies (logo) and customers (picture).
 * Each record belongs to exactly one company or one customer.
 */
#[ORM\Entity(repositoryClass: LogoRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'logo')]
class Logo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'logos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    /** Public relative path, e.g. /uploads/company/abc123.png */
    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isPrimary = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }
}
