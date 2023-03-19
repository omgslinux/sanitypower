<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @ORM\Table(name="company",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"realname", "country"})}
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
     * @ORM\OneToMany(targetEntity=CompanyIncoming::class, mappedBy="Company", orphanRemoval=true, cascade={"persist"})
     * @ORM\OrderBy({"year" = "ASC"})
     */
    private $companyIncomings;

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
    * @ORM\ManyToOne(targetEntity=CompanyActivityCategory::class, inversedBy="companies")
    * @ORM\JoinColumn(nullable=true)
    */
    private $category;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * Empresas de las que la actual es accionista (posee)
     *
     * @ORM\OneToMany(targetEntity=Shareholder::class, mappedBy="holder", cascade={"persist"})
     */
    private $subsidiaries;

    /**
     * Empresas que son propietarias de la actual
     *
     * @ORM\OneToMany(targetEntity=Shareholder::class, mappedBy="subsidiary", cascade={"persist"})
     */
    private $holders;

    /**
     * @ORM\OneToMany(targetEntity=StaffMembership::class, mappedBy="company")
     */
    private $staffMemberships;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $inList;

    /**
     * @ORM\OneToMany(targetEntity=CompanyRelationship::class, mappedBy="parent")
     */
    private $parentRelationships;

    /**
     * @ORM\OneToMany(targetEntity=CompanyRelationship::class, mappedBy="child")
     */
    private $childRelationships;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $realname;

    public function __construct()
    {
        $this->companyIncomings = new ArrayCollection();
        $this->companyEvents = new ArrayCollection();
        $this->companyRelationships = new ArrayCollection();
        $this->subsidiiaries = new ArrayCollection();
        $this->holders = new ArrayCollection();
        $this->staffMemberships = new ArrayCollection();
        $this->child = new ArrayCollection();
        $this->parentRelationships = new ArrayCollection();
        $this->childRelationships = new ArrayCollection();
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

    public function getCountrySuffix(): ?string
    {
        if ($this->getLevel()->getLevel()!='Matriz') {
            return '(' . $this->country . ')';
        }
        return '[' . $this->country . ']';
    }

    /**
     * @return Collection|CompanyIncoming[]
     */
    public function getCompanyIncomings(): Collection
    {
        return $this->companyIncomings;
    }

    public function addCompanyIncoming(CompanyIncoming $incoming): self
    {
        if (!$this->companyIncomings->contains($incoming)) {
            $this->companyIncomings[] = $incoming;
            $incoming->setCompany($this);
        }

        return $this;
    }

    public function removeCompanyIncoming(CompanyIncoming $incoming): self
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
     * @return Collection|Shareholder[]
     */
    public function getSubsidiaries(): Collection
    {
        return $this->subsidiaries;
    }

    public function addSubsidiary(Shareholder $sub): self
    {
        if (!$this->subsidiaries->contains($sub)) {
            $this->subsidiaries[] = $sub;
            $sub->setSubsidiary($this);
        }

        return $this;
    }

    public function removeSubsidiary(Shareholder $sub): self
    {
        if ($this->heldCompanys->removeElement($sub)) {
            // set the owning side to null (unless already changed)
            if ($sub->getCompany() === $this) {
                $sub->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Shareholder[]
     */
    public function getHolders(): Collection
    {
        return $this->holders;
    }

    public function addHolder(Shareholder $holder): self
    {
        if (!$this->holders->contains($holder)) {
            $this->holders[] = $holder;
            $holder->setHolder($this);
        }

        return $this;
    }

    public function removeHolder(Shareholder $holder): self
    {
        if ($this->holders->removeElement($holder)) {
            // set the owning side to null (unless already changed)
            if ($holder->getHolder() === $this) {
                $holder->setHolder(null);
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

    public function getCategory(): ?CompanyActivityCategory
    {
        return $this->category;
    }

    public function setCategory(?CompanyActivityCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function isInList(): ?bool
    {
        return $this->inList;
    }

    public function setInList(?bool $inList): self
    {
        $this->inList = $inList;

        return $this;
    }

    /**
     * @return Collection<int, CompanyRelationship>
     */
    public function getParentRelationships(): Collection
    {
        return $this->parentRelationships;
    }

    public function addParentRelationship(CompanyRelationship $parentRelationship): self
    {
        if (!$this->parentRelationships->contains($parentRelationship)) {
            $this->parentRelationships[] = $parentRelationship;
            $parentRelationship->setParent($this);
        }

        return $this;
    }

    public function removeParentRelationship(CompanyRelationship $parentRelationship): self
    {
        if ($this->parentRelationships->removeElement($parentRelationship)) {
            // set the owning side to null (unless already changed)
            if ($parentRelationship->getParent() === $this) {
                $parentRelationship->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompanyRelationship>
     */
    public function getChildRelationships(): Collection
    {
        return $this->childRelationships;
    }

    public function addChildRelationship(CompanyRelationship $childRelationship): self
    {
        if (!$this->childRelationships->contains($childRelationship)) {
            $this->childRelationships[] = $childRelationship;
            $childRelationship->setChild($this);
        }

        return $this;
    }

    public function removeChildRelationship(CompanyRelationship $childRelationship): self
    {
        if ($this->childRelationships->removeElement($childRelationship)) {
            // set the owning side to null (unless already changed)
            if ($childRelationship->getChild() === $this) {
                $childRelationship->setChild(null);
            }
        }

        return $this;
    }

    public function getRealname(): ?string
    {
        return $this->realname;
    }

    public function setRealname(string $realname): self
    {
        $this->realname = $realname;

        return $this;
    }

    public function __toString()
    {
        return ($this->getRealname()??$this->getFullname()) . ' ' . $this->getCountrySuffix();
    }
}
