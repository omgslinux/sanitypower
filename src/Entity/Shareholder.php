<?php

namespace App\Entity;

use App\Repository\ShareholderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'shareholders')]
#[ORM\UniqueConstraint(columns: ['holder_id', 'subsidiary_id', 'via'])]
#[ORM\Entity(repositoryClass: ShareholderRepository::class)]
class Shareholder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * En accionistas, $company es $parent, la que es poseída por un holder
     */
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'subsidiaries')]
    #[ORM\JoinColumn(nullable: false)]
    private $holder;

    /**
     * En accionistas, $holder es quien tiene acciones de $company
     */
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'holders')]
    #[ORM\JoinColumn(nullable: false)]
    private $subsidiary;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private $direct;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private $total;

    #[ORM\Column(type: 'boolean')]
    private $via = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $skip;

    #[ORM\ManyToOne(targetEntity: ShareholderCategory::class, inversedBy: 'shareholders')]
    #[ORM\JoinColumn(nullable: true)]
    private $holderCategory;

    #[ORM\Column(type: 'json', nullable: true)]
    private $data = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHolder(): ?Company
    {
        return $this->holder;
    }

    public function setHolder(?Company $company): self
    {
        $this->holder = $company;

        return $this;
    }

    public function getSubsidiary(): ?Company
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(?Company $value): self
    {
        $this->subsidiary = $value;

        return $this;
    }

    public function getDirect(): ?string
    {
        return $this->direct;
    }

    public function setDirect(?string $direct): self
    {
        $this->direct = $direct;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(?string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getVia(): bool
    {
        return $this->via;
    }

    public function setVia(int $via): self
    {
        $this->via = $via;

        return $this;
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

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function __toString()
    {
        return $this->getHolder() . ($this->getVia() !=0 ? ' (via its funds)':'');
    }
}
