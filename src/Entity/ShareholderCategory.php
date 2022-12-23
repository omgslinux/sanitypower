<?php

namespace App\Entity;

use App\Repository\ShareholderCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ShareholderCategoryRepository::class)
 * @ORM\Table(name="shareholder_categories",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"letter"})}
 * )
 */
class ShareholderCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $letter;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=Shareholder::class, mappedBy="holderCategory")
     */
    private $shareholders;

    public function __construct()
    {
        $this->shareholders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLetter(): ?string
    {
        return $this->letter;
    }

    public function setLetter(string $letter): self
    {
        $this->letter = $letter;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Shareholder>
     */
    public function getShareholders(): Collection
    {
        return $this->shareholders;
    }

    public function addShareholder(Shareholder $shareholder): self
    {
        if (!$this->shareholders->contains($shareholder)) {
            $this->shareholders[] = $shareholder;
            $shareholder->setHolderCategory($this);
        }

        return $this;
    }

    public function removeShareholder(Shareholder $shareholder): self
    {
        if ($this->shareholders->removeElement($shareholder)) {
            // set the owning side to null (unless already changed)
            if ($shareholder->getHolderCategory() === $this) {
                $shareholder->setHolderCategory(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getLetter() . ' - ' . $this->getDescription();
    }
}
