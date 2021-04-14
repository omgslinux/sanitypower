<?php

namespace App\Entity;

use App\Repository\StaffMembershipRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StaffMembershipRepository::class)
 * @ORM\Table(name="staff_memberships",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"company_id", "staffmember_id", "title_id"})}
 * )
 */
class StaffMembership
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="staffMemberships")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity=StaffMembers::class, inversedBy="staffMemberships")
     * @ORM\JoinColumn(nullable=false)
     */
    private $staffmember;

    /**
     * @ORM\ManyToOne(targetEntity=BoardTitle::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $title;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $datefrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $dateto;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getStaffmember(): ?StaffMembers
    {
        return $this->staffmember;
    }

    public function setStaffmember(?StaffMembers $staffmember): self
    {
        $this->staffmember = $staffmember;

        return $this;
    }

    public function getTitle(): ?BoardTitle
    {
        return $this->title;
    }

    public function setTitle(?BoardTitle $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDatefrom(): ?\DateTimeInterface
    {
        return $this->datefrom;
    }

    public function setDatefrom(?\DateTimeInterface $datefrom): self
    {
        $this->datefrom = $datefrom;

        return $this;
    }

    public function getDateto(): ?\DateTimeInterface
    {
        return $this->dateto;
    }

    public function setDateto(?\DateTimeInterface $dateto): self
    {
        $this->dateto = $dateto;

        return $this;
    }
}
