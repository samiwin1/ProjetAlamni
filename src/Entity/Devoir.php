<?php

namespace App\Entity;

use App\Repository\DevoirRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DevoirRepository::class)]
class Devoir
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.", maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $titreD = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(min: 10, max: 255, minMessage: "La description doit contenir au moins {{ limit }} caractères.", maxMessage: "La description ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $descrD = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de remise ne peut pas être vide.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateD = null;

   

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le commentaire ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "Le commentaire doit contenir au moins {{ limit }} caractères.", maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $comment = null;


    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'devoirs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Un cours doit être associé au devoir.")]
    private ?Cours $cours = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreD(): ?string
    {
        return $this->titreD;
    }

    public function setTitreD(string $titreD): static
    {
        $this->titreD = $titreD;
        return $this;
    }

    public function getDescrD(): ?string
    {
        return $this->descrD;
    }

    public function setDescrD(string $descrD): static
    {
        $this->descrD = $descrD;
        return $this;
    }

    public function getDateD(): ?\DateTimeInterface
    {
        return $this->dateD;
    }

    public function setDateD(\DateTimeInterface $dateD): static
    {
        $this->dateD = $dateD;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;
        return $this;
    }
}
