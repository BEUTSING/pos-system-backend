<?php

namespace App\Entity\Company;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\Company\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nameComp = null;

    #[ORM\Column(length: 180)]
    private ?string $emailComp = null;

    #[ORM\Column(length: 15)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column]
    private ?int $numEmpl = null;

    #[ORM\Column(length: 255)]
    private ?string $siteWeb = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameComp(): ?string
    {
        return $this->nameComp;
    }

    public function setNameComp(string $nameComp): static
    {
        $this->nameComp = $nameComp;

        return $this;
    }

    public function getEmailComp(): ?string
    {
        return $this->emailComp;
    }

    public function setEmailComp(string $emailComp): static
    {
        $this->emailComp = $emailComp;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getNumEmpl(): ?int
    {
        return $this->numEmpl;
    }

    public function setNumEmpl(int $numEmpl): static
    {
        $this->numEmpl = $numEmpl;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;

        return $this;
    }
}
