<?php

namespace App\Entity;

use App\Repository\ShareholderCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ShareholderCategoryRepository::class)
 * @ORM\Table(name="shareholder_typès",
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
     * @ORM\Column(type="string", length=2)
     */
    private $letter;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $description;

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

    public function __toString()
    {
        return $this->getLetter() . ' - ' . $this->getDescription();
    }
}
