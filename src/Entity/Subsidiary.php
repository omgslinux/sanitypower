<?php

namespace App\Entity;

use App\Repository\SubsidiaryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SubsidiaryRepository::class)
 */
class Subsidiary
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="ownedSubsidiaries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="ownerSubsidiaries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $owned;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $percent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Company
    {
        return $this->owner;
    }

    public function setOwner(?Company $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwned(): ?Company
    {
        return $this->owned;
    }

    public function setOwned(?Company $owned): self
    {
        $this->owned = $owned;

        return $this;
    }

    public function getPercent(): ?string
    {
        return $this->percent;
    }

    public function setPercent(?string $percent): self
    {
        $this->percent = $percent;

        return $this;
    }
}
