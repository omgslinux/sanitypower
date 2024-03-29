<?php

namespace App\Entity;

use App\Repository\CompanyIncomingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'company_incomings')]
#[ORM\UniqueConstraint(columns: ['incoming_date', 'company_id'])]
#[ORM\Entity(repositoryClass: CompanyIncomingRepository::class)]
class CompanyIncoming
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'companyIncomings')]
    #[ORM\JoinColumn(nullable: false)]
    private $Company;

    #[ORM\Column(type: 'float', nullable: true)]
    private $amount;

    #[ORM\Column(type: 'date', name: 'incoming_date')]
    private $year;

    #[ORM\ManyToOne(targetEntity: Currency::class, inversedBy: 'incomings')]
    #[ORM\JoinColumn(nullable: false)]
    private $currency;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->Company;
    }

    public function setCompany(?Company $Company): self
    {
        $this->Company = $Company;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getYear(): ?\DateTimeInterface
    {
        return $this->year;
    }

    public function setYear(\DateTimeInterface $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }
}
