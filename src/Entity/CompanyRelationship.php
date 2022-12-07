<?php

namespace App\Entity;

use App\Repository\CompanyRelationshipRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompanyRelationshipRepository::class)
 * @ORM\Table(name="company_relationships")
 */
class CompanyRelationship
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="text")
     */
    private $notes;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="parentRelationships")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="childRelationships")
     * @ORM\JoinColumn(nullable=false)
     */
    private $child;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getParent(): ?Company
    {
        return $this->parent;
    }

    public function setParent(?Company $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChild(): ?Company
    {
        return $this->child;
    }

    public function setChild(?Company $child): self
    {
        $this->child = $child;

        return $this;
    }


}
