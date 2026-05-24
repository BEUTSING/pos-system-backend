<?php

namespace App\Entity\Security;

use App\Entity\Checkout\Cancellation;
use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\Sale;
use App\Entity\Company\Company;
use App\Entity\Stock\Stockmovement;
 use App\Entity\Traits\ContactTrait;
use App\Entity\Traits\TimestampableTrait;
use App\Repository\Security\UserRepository as SecurityUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SecurityUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{   
    use TimestampableTrait; 
     use ContactTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email address.')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private ?string $color = null;

    /**
     * @var Collection<int, CustomerOrder>
     */
    #[ORM\OneToMany(targetEntity: CustomerOrder::class, mappedBy: 'waiter')]
    private Collection $customerOrders;

    /**
     * @var Collection<int, Sale>
     */
    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'teller')]
    private Collection $sales;

    /**
     * @var Collection<int, Cancellation>
     */
    #[ORM\OneToMany(targetEntity: Cancellation::class, mappedBy: 'user')]
    private Collection $cancellations;

    /**
     * @var Collection<int, Stockmovement>
     */
    #[ORM\OneToMany(targetEntity: Stockmovement::class, mappedBy: 'user')]
    private Collection $stockmovements;

    /**
     * @var Collection<int, CustomerOrder>
     */
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'owner')]
    private Collection $companies;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;


    public function __construct()
    {
        $this->customerOrders = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->cancellations = new ArrayCollection();
        $this->stockmovements = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_ADMIN';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
     * @return Collection<int, CustomerOrder>
     */
    public function getCustomerOrders(): Collection
    {
        return $this->customerOrders;
    }

    public function addCustomerOrder(CustomerOrder $customerOrder): static
    {
        if (!$this->customerOrders->contains($customerOrder)) {
            $this->customerOrders->add($customerOrder);
            $customerOrder->setWaiter($this);
        }

        return $this;
    }

    public function removeCustomerOrder(CustomerOrder $customerOrder): static
    {
        if ($this->customerOrders->removeElement($customerOrder)) {
            // set the owning side to null (unless already changed)
            if ($customerOrder->getWaiter() === $this) {
                $customerOrder->setWaiter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sale>
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): static
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setTeller($this);
        }

        return $this;
    }

    public function removeSale(Sale $sale): static
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getTeller() === $this) {
                $sale->setTeller(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cancellation>
     */
    public function getCancellations(): Collection
    {
        return $this->cancellations;
    }

    public function addCancellation(Cancellation $cancellation): static
    {
        if (!$this->cancellations->contains($cancellation)) {
            $this->cancellations->add($cancellation);
            $cancellation->setUser($this);
        }

        return $this;
    }

    public function removeCancellation(Cancellation $cancellation): static
    {
        if ($this->cancellations->removeElement($cancellation)) {
            // set the owning side to null (unless already changed)
            if ($cancellation->getUser() === $this) {
                $cancellation->setUser(null);
            }
        }

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
            $stockmovement->setUser($this);
        }

        return $this;
    }

    public function removeStockmovement(Stockmovement $stockmovement): static
    {
        if ($this->stockmovements->removeElement($stockmovement)) {
            // set the owning side to null (unless already changed)
            if ($stockmovement->getUser() === $this) {
                $stockmovement->setUser(null);
            }
        }

        return $this;
    }

    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addOwner(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setOwner($this);
        }

        return $this;
    }

    public function removeOwner(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getOwner() === $this) {
                $company->setOwner(null);
            }
        }

        return $this;
    }
        public function getCompany(): ?Company
        {
            return $this->company;
        }

        public function setCompany(?Company $company): static
        {
            $this->company = $this->company;
            return $this;
        }

}
