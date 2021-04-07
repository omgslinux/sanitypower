<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CurrencyRepository::class)
 */
class Currency
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=4)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=4)
     */
    private $symbol;

    /**
     * @ORM\OneToMany(targetEntity=CurrencyExchange::class, mappedBy="currency")
     */
    private $currencyExchanges;

    /**
     * @ORM\OneToMany(targetEntity=Incoming::class, mappedBy="currency")
     */
    private $incomings;

    public function __construct()
    {
        $this->currencyExchanges = new ArrayCollection();
        $this->incomings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * @return Collection|CurrencyExchange[]
     */
    public function getCurrencyExchanges(): Collection
    {
        return $this->currencyExchanges;
    }

    public function addCurrencyExchange(CurrencyExchange $currencyExchange): self
    {
        if (!$this->currencyExchanges->contains($currencyExchange)) {
            $this->currencyExchanges[] = $currencyExchange;
            $currencyExchange->setCurrency($this);
        }

        return $this;
    }

    public function removeCurrencyExchange(CurrencyExchange $currencyExchange): self
    {
        if ($this->currencyExchanges->removeElement($currencyExchange)) {
            // set the owning side to null (unless already changed)
            if ($currencyExchange->getCurrency() === $this) {
                $currencyExchange->setCurrency(null);
            }
        }

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
            $incoming->setCurrency($this);
        }

        return $this;
    }

    public function removeIncoming(Incoming $incoming): self
    {
        if ($this->incomings->removeElement($incoming)) {
            // set the owning side to null (unless already changed)
            if ($incoming->getCurrency() === $this) {
                $incoming->setCurrency(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getName() . ' (' . $this->getCode(). ')';
    }
}
