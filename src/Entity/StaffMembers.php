<?php

namespace App\Entity;

use App\Repository\StaffMembersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StaffMembersRepository::class)
 * ORM\Table(name="members",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"surname", "name"})
 * )
 */
class StaffMembers
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
    private $surname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity=StaffMembers::class, mappedBy="staffMember")
     */
    private $staffMemberships;

    public function __construct()
    {
        $this->StaffMemberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function __toString()
    {
        return $this->getSurname() . ', ' . $this->getName();
    }

    /**
     * @return Collection|self[]
     */
    public function getStaffMemberships(): Collection
    {
        return $this->StaffMemberships;
    }

    public function addStaffMembership(self $staffMembership): self
    {
        if (!$this->StaffMemberships->contains($staffMembership)) {
            $this->StaffMemberships[] = $staffMembership;
            $staffMembership->setStaffMembers($this);
        }

        return $this;
    }

    public function removeStaffMembership(self $staffMembership): self
    {
        if ($this->StaffMemberships->removeElement($staffMembership)) {
            // set the owning side to null (unless already changed)
            if ($staffMembership->getStaffMembers() === $this) {
                $staffMembership->setStaffMembers(null);
            }
        }

        return $this;
    }
}
