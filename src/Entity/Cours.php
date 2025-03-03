<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.", maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "La description doit contenir au moins {{ limit }} caractères.", maxMessage: "La description ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $descrC = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La matière ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "La matière doit contenir au moins {{ limit }} caractères.", maxMessage: "La matière ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $matiereC = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date du cours ne peut pas être vide.")]
    private ?\DateTimeInterface $dateC = null;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Devoir::class, cascade: ['remove'])]
    private Collection $devoirs;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $supportC = null;

    #[ORM\Column(length: 255)]
  
    private ?string $niveau = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'cours')]
    private Collection $ratings;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'cours')]
    private Collection $Ratings;

    public function __construct()
    {
        $this->devoirs = new ArrayCollection();
      
        $this->dateC = new \DateTime();
        $this->ratings = new ArrayCollection();
        $this->Ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescrC(): ?string
    {
        return $this->descrC;
    }

    public function setDescrC(?string $descrC): self
    {
        $this->descrC = $descrC;
        return $this;
    }

    public function getMatiereC(): ?string
    {
        return $this->matiereC;
    }

    public function setMatiereC(?string $matiereC): self
    {
        $this->matiereC = $matiereC;
        return $this;
    }

    public function getDateC(): ?\DateTimeInterface
    {
        return $this->dateC;
    }

    public function setDateC(?\DateTimeInterface $dateC): self
    {
        $this->dateC = $dateC;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getDevoirs(): Collection
    {
        return $this->devoirs;
    }

    public function addDevoir(Devoir $devoir): self
    {
        if (!$this->devoirs->contains($devoir)) {
            $this->devoirs->add($devoir);
            $devoir->setCours($this);
        }
        return $this;
    }

    public function removeDevoir(Devoir $devoir): self
    {
        if ($this->devoirs->removeElement($devoir)) {
            if ($devoir->getCours() === $this) {
                $devoir->setCours(null);
            }
        }
        return $this;
    }

    public function getSupportC(): ?string
    {
        return $this->supportC;
    }

    public function setSupportC(?string $supportC): self
    {
        $this->supportC = $supportC;
        return $this;
    }

   



    public function getniveau(): ?string
    {
        return $this->niveau;
    }

    public function setniveau(string $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setCours($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getCours() === $this) {
                $rating->setCours(null);
            }
        }

        return $this;
    }
}
