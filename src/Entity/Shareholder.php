<?php

namespace App\Entity;

use App\Repository\ShareholderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ShareholderRepository::class)
 * @ORM\Table(name="shareholders",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"company_id", "holder_id"})}
 * )
*/
class Shareholder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="shareholders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="holders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $holder;

    /**
     * @ORM\ManyToOne(targetEntity=ShareholderCategory::class)
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OrderBy({"letter" = "ASC"})
     */
    private $holderCategory;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $directOwnership;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $totalOwnership;

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

    public function getHolder(): ?Company
    {
        return $this->holder;
    }

    public function setHolder(?Company $holder): self
    {
        $this->holder = $holder;

        return $this;
    }

    public function getHolderCategory(): ?ShareholderCategory
    {
        return $this->holderCategory;
    }

    public function setHolderCategory(?ShareholderCategory $holderCategory): self
    {
        $this->holderCategory = $holderCategory;

        return $this;
    }

    public function getDirectOwnership(): ?string
    {
        return $this->directOwnership;
    }

    public function setDirectOwnership(?string $directOwnership): self
    {
        $this->directOwnership = $directOwnership;

        return $this;
    }

    public function getTotalOwnership(): ?string
    {
        return $this->totalOwnership;
    }

    public function setTotalOwnership(?string $totalOwnership): self
    {
        $this->totalOwnership = $totalOwnership;

        return $this;
    }
}
