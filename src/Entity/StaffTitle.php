<?php

namespace App\Entity;

use App\Repository\StaffTitleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StaffTitleRepository::class)
 * @ORM\Table(name="staff_titles",
 *   uniqueConstraints={@ORM\UniqueConstraint(columns={"name"})}
 * )
 */
class StaffTitle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $alias;

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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function __toString()
    {
        return $this->getName() . ' (' . $this->getAlias() . ')';
    }
}
