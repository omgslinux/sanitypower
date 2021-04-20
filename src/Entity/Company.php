<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @ORM\Table(name="company",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"fullname"})}
 * )
 */
class Company
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullname;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $ShortName;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $country;

    /**
     * @ORM\OneToMany(targetEntity=Incoming::class, mappedBy="Company", orphanRemoval=true)
     * @ORM\OrderBy({"year" = "ASC"})
     */
    private $incomings;

    /**
     * @ORM\OneToMany(targetEntity=CompanyEvent::class, mappedBy="company")
     * @ORM\OrderBy({"date" = "ASC"})
     */
    private $companyEvents;

    /**
     * @ORM\ManyToOne(targetEntity=CompanyLevel::class, inversedBy="companies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $level;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity=CompanyRelationship::class, mappedBy="parent", orphanRemoval=true)
     */
    private $companyRelationships;

    /**
     * @ORM\OneToMany(targetEntity=Shareholder::class, mappedBy="company")
     */
    private $companyHolders;

    /**
     * @ORM\OneToMany(targetEntity=StaffMembership::class, mappedBy="company")
     */
    private $staffMemberships;

    /**
     * @ORM\OneToMany(targetEntity=Subsidiary::class, mappedBy="owner")
     */
    private $ownerSubsidiaries;

    /**
     * @ORM\OneToMany(targetEntity=Subsidiary::class, mappedBy="owned")
     * @ORM\OrderBy({"date" = "ASC"})
     */
    private $ownedSubdiaries;

    public function __construct()
    {
        $this->incomings = new ArrayCollection();
        $this->companyEvents = new ArrayCollection();
        $this->companyRelationships = new ArrayCollection();
        $this->companyHolders = new ArrayCollection();
        $this->staffMemberships = new ArrayCollection();
        $this->ownerSubsidiaries = new ArrayCollection();
        $this->ownedSubdiaries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->ShortName;
    }

    public function setShortName(?string $ShortName): self
    {
        $this->ShortName = $ShortName;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection|Incoming[]
     */
    public function getIncomings(): Collection
    {
        return $this->incomings;
    }

    public function addIncoming(Incoming $incoming): self
    {
        if (!$this->incomings->contains($incoming)) {
            $this->incomings[] = $incoming;
            $incoming->setCompany($this);
        }

        return $this;
    }

    public function removeIncoming(Incoming $incoming): self
    {
        if ($this->incomings->removeElement($incoming)) {
            // set the owning side to null (unless already changed)
            if ($incoming->getCompany() === $this) {
                $incoming->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CompanyEvents[]
     */
    public function getCompanyEvents(): Collection
    {
        return $this->companyEvents;
    }

    public function addCompanyEvent(CompanyEvent $companyEvent): self
    {
        if (!$this->companyEvents->contains($companyEvent)) {
            $this->companyEvents[] = $companyEvent;
            $companyEvent->setCompany($this);
        }

        return $this;
    }

    public function removeCompanyEvent(CompanyEvent $companyEvent): self
    {
        if ($this->companyEvents->removeElement($companyEvent)) {
            // set the owning side to null (unless already changed)
            if ($companyEvent->getCompany() === $this) {
                $companyEvent->setCompany(null);
            }
        }

        return $this;
    }

    public function getLevel(): ?CompanyLevel
    {
        return $this->level;
    }

    public function setLevel(?CompanyLevel $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection|CompanyRelationship[]
     */
    public function getCompanyRelationships(): Collection
    {
        return $this->companyRelationships;
    }

    public function addCompanyRelationship(CompanyRelationship $companyRelationship): self
    {
        if (!$this->companyRelationships->contains($companyRelationship)) {
            $this->companyRelationships[] = $companyRelationship;
            $companyRelationship->setParent($this);
        }

        return $this;
    }

    public function removeCompanyRelationship(CompanyRelationship $companyRelationship): self
    {
        if ($this->companyRelationships->removeElement($companyRelationship)) {
            // set the owning side to null (unless already changed)
            if ($companyRelationship->getParent() === $this) {
                $companyRelationship->setParent(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getFullname();
    }

    /**
     * @return Collection|Shareholder[]
     */
    public function getCompanyHolders(): Collection
    {
        return $this->companyHolders;
    }

    public function addCompanyHolder(Shareholder $holder): self
    {
        if (!$this->companyHolders->contains($holder)) {
            $this->companyHolders[] = $holder;
            $holder->setCompany($this);
        }

        return $this;
    }

    public function removeCompanyHolder(Shareholder $holder): self
    {
        if ($this->companyHolders->removeElement($holder)) {
            // set the owning side to null (unless already changed)
            if ($holder->getCompany() === $this) {
                $holder->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|StaffMembership[]
     */
    public function getStaffMemberships(): Collection
    {
        return $this->staffMemberships;
    }

    public function addStaffMembership(StaffMembership $staffMembership): self
    {
        if (!$this->staffMemberships->contains($staffMembership)) {
            $this->staffMemberships[] = $staffMembership;
            $staffMembership->setCompany($this);
        }

        return $this;
    }

    public function removeStaffMembership(StaffMembership $staffMembership): self
    {
        if ($this->staffMemberships->removeElement($staffMembership)) {
            // set the owning side to null (unless already changed)
            if ($staffMembership->getCompany() === $this) {
                $staffMembership->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Subsidiary[]
     */
    public function getOwnerSubsidiaries(): Collection
    {
        return $this->ownerSubsidiaries;
    }

    public function addOwnerSubsidiary(Subsidiary $ownerSubsidiary): self
    {
        if (!$this->ownerSubsidiaries->contains($ownerSubsidiary)) {
            $this->ownerSubsidiaries[] = $ownerSubsidiary;
            $ownerSubsidiary->setOwner($this);
        }

        return $this;
    }

    public function removeOwnerSubsidiary(Subsidiary $ownerSubsidiary): self
    {
        if ($this->ownerSubsidiaries->removeElement($ownerSubsidiary)) {
            // set the owning side to null (unless already changed)
            if ($ownerSubsidiary->getOwner() === $this) {
                $ownerSubsidiary->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Subsidiary[]
     */
    public function getOwnedSubdiaries(): Collection
    {
        return $this->ownedSubdiaries;
    }

    public function addOwnedSubdiary(Subsidiary $ownedSubdiary): self
    {
        if (!$this->ownedSubdiaries->contains($ownedSubdiary)) {
            $this->ownedSubdiaries[] = $ownedSubdiary;
            $ownedSubdiary->setOwned($this);
        }

        return $this;
    }

    public function removeOwnedSubdiary(Subsidiary $ownedSubdiary): self
    {
        if ($this->ownedSubdiaries->removeElement($ownedSubdiary)) {
            // set the owning side to null (unless already changed)
            if ($ownedSubdiary->getOwned() === $this) {
                $ownedSubdiary->setOwned(null);
            }
        }

        return $this;
    }
}
