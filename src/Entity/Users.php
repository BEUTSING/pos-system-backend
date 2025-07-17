<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 50)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $color = null;


    /**
     * @var Collection<int, Stockmovement>
     */
    #[ORM\OneToMany(targetEntity: Stockmovement::class, mappedBy: 'username')]
    private Collection $stockmovements;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Contact $contact = null;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'cashier')]
    private Collection $invoices;

    public function __construct()
    {        $this->stockmovements = new ArrayCollection();
    $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

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
            $stockmovement->setUsername($this);
        }

        return $this;
    }

    public function removeStockmovement(Stockmovement $stockmovement): static
    {
        if ($this->stockmovements->removeElement($stockmovement)) {
            // set the owning side to null (unless already changed)
            if ($stockmovement->getUsername() === $this) {
                $stockmovement->setUsername(null);
            }
        }

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setCashier($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getCashier() === $this) {
                $invoice->setCashier(null);
            }
        }

        return $this;
    }
}
