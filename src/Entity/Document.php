<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $namedoc = null;

    #[ORM\Column(length: 255)]
    private ?string $typedoc = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNamedoc(): ?string
    {
        return $this->namedoc;
    }

    public function setNamedoc(string $namedoc): static
    {
        $this->namedoc = $namedoc;

        return $this;
    }

    public function getTypedoc(): ?string
    {
        return $this->typedoc;
    }

    public function setTypedoc(string $typedoc): static
    {
        $this->typedoc = $typedoc;

        return $this;
    }
}
