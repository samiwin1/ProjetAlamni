<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponsereclamation::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $reponses;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "Veuillez entrer un email valide.")]
    private ?string $user_email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le destinataire est obligatoire.")]
    #[Assert\Email(message: "Veuillez entrer un email valide.")]
    private ?string $admin_mail = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez sélectionner un rôle.")]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'objet est obligatoire.")]
    private ?string $objet = null;

    #[ORM\Column(length: 2000)]
    #[Assert\NotBlank(message: "Veuillez entrer une description.")]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_soumission = null;

    #[ORM\Column(length: 255, options: ['default' => 'En attente'])]
    private ?string $status = 'En attente';

    public function __construct()
    {
        $this->date_soumission = new \DateTime();
        $this->status = 'En attente';
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getUserEmail(): ?string { return $this->user_email; }
    public function setUserEmail(string $user_email): self { $this->user_email = $user_email; return $this; }

    public function getAdminMail(): ?string { return $this->admin_mail; }
    public function setAdminMail(string $admin_mail): self { $this->admin_mail = $admin_mail; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getObjet(): ?string { return $this->objet; }
    public function setObjet(string $objet): self { $this->objet = $objet; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getDateSoumission(): ?\DateTimeInterface { return $this->date_soumission; }
    public function setDateSoumission(\DateTimeInterface $date_soumission): self { $this->date_soumission = $date_soumission; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    /**
     * @return Collection<int, Reponsereclamation>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponsereclamation $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setReclamation($this);
        }

        return $this;
    }

    public function removeReponse(Reponsereclamation $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }

        return $this;
    }
}
