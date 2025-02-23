<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\HasLifecycleCallbacks] // Enables lifecycle event callbacks
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The name cannot be blank.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "The name cannot be longer than {{ limit }} characters."
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] // Allow null values
    #[Assert\NotBlank(message: "The start time cannot be blank.")]
    #[Assert\DateTime(message: "The start time must be a valid date and time.")]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] // Allow null values
    #[Assert\NotBlank(message: "The end time cannot be blank.")]
    #[Assert\DateTime(message: "The end time must be a valid date and time.")]
    #[Assert\GreaterThan(
        propertyPath: "startTime",
        message: "The end time must be after the start time."
    )]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $modifiedDate = null;

    #[ORM\ManyToOne(inversedBy: 'plannings')]
    #[Assert\NotNull(message: "Please select a seance.")]
    private ?Seance $seance = null;

    #[ORM\ManyToOne(inversedBy: 'plannings')]
    private ?User $user = null;
  
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)] // Allow null temporarily
    #[Assert\NotNull(message: "Please select a teacher.")]
    private ?User $teacher = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Student level cannot be blank.")]
    #[Assert\Choice(
        choices: ["Collège", "Lycée"],
        message: "Please select a valid student level."
    )]
    private ?string $studentLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getUploadedDate(): ?\DateTimeInterface
    {
        return $this->uploadedDate;
    }

    #[ORM\PrePersist] // Automatically sets `uploadedDate` before inserting
    public function setUploadedDate(): void
    {
        if ($this->uploadedDate === null) {
            $this->uploadedDate = new \DateTime();
        }
    }

    public function getModifiedDate(): ?\DateTimeInterface
    {
        return $this->modifiedDate;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate] // Updates `modifiedDate` before every update
    public function setModifiedDate(): void
    {
        $this->modifiedDate = new \DateTime();
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getStudentLevel(): ?string
    {
        return $this->studentLevel;
    }

    public function setStudentLevel(?string $studentLevel): static
    {
        $this->studentLevel = $studentLevel;
        return $this;
    }
}