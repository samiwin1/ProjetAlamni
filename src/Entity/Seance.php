<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)] // Allow null values
    #[Assert\NotBlank(message: "The name cannot be blank.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "The name cannot be longer than {{ limit }} characters."
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)] // Allow null values
    #[Assert\NotBlank(message: "The description cannot be blank.")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "The description must be at least {{ limit }} characters long.",
        maxMessage: "The description cannot be longer than {{ limit }} characters."
    )]
    private ?string $description = null;

    /**
     * @var Collection<int, Planning>
     */
    #[ORM\OneToMany(targetEntity: Planning::class, mappedBy: 'seance', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $plannings;

    public function __construct()
    {
        $this->plannings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static // Allow null values
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static // Allow null values
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, Planning>
     */
    public function getPlannings(): Collection
    {
        return $this->plannings;
    }

    public function addPlanning(Planning $planning): static
    {
        if (!$this->plannings->contains($planning)) {
            $this->plannings->add($planning);
            $planning->setSeance($this);
        }

        return $this;
    }

    public function removePlanning(Planning $planning): static
    {
        if ($this->plannings->removeElement($planning)) {
            // set the owning side to null (unless already changed)
            if ($planning->getSeance() === $this) {
                $planning->setSeance(null);
            }
        }

        return $this;
    }
}