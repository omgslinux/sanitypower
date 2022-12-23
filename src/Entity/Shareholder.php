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
    * En accionistas, $company es $parent, la que es poseída por un holder
    *
    * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="companyHolders")
    * @ORM\JoinColumn(nullable=false)
    */
    private $company;

    /**
    * En accionistas, $holder es quien tiene acciones de $company
    *
    * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="heldCompanys")
    * @ORM\JoinColumn(nullable=false)
    */
    private $holder;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $directOwnership;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $totalOwnership;

    /**
     * @ORM\Column(type="boolean")
     */
    private $via = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $skip;

    /**
     * @ORM\ManyToOne(targetEntity=ShareholderCategory::class, inversedBy="shareholders")
     * @ORM\JoinColumn(nullable=true)
     */
    private $holderCategory;

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

    public function getVia()
    {
        return $this->via;
    }

    public function setVia(int $via): self
    {
        $this->via = $via;

        return $this;
    }

    public function __toString()
    {
        return $this->getHolder() . ($this->getVia() !=0 ? ' (via its funds)':'');
    }

    public function isSkip(): ?bool
    {
        return $this->skip;
    }

    public function setSkip(?bool $skip): self
    {
        $this->skip = $skip;

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
}
