<?php

namespace App\Entity;
use App\Repository\UserType;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $mot_de_passe = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_inscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_niveau = null;

    /**
     * @var Collection<int, Planning>
     */
    #[ORM\OneToMany(targetEntity: Planning::class, mappedBy: 'user')]
    private Collection $plannings;

    /**
     * @var Collection<int, Reponsereclamation>
     */
    #[ORM\OneToMany(targetEntity: Reponsereclamation::class, mappedBy: 'admin_id')]
    private Collection $reponsereclamations;

    public function __construct()
    {
        $this->plannings = new ArrayCollection();
        $this->reponsereclamations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getRoles(): array
{
    return [$this->role ?? 'ROLE_USER']; // Retourne un tableau contenant le rôle de l'utilisateur
}

    
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): self
    {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTimeInterface $date_inscription): self
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getNomNiveau(): ?string
    {
        return $this->nom_niveau;
    }

    public function setNomNiveau(?string $nom_niveau): self
    {
        $this->nom_niveau = $nom_niveau;
        return $this;
    }

    public function getFormattedDateInscription(): string
    {
        return $this->date_inscription ? $this->date_inscription->format('d/m/Y H:i') : '';
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
            $planning->setUser($this);
        }

        return $this;
    }

    public function removePlanning(Planning $planning): static
    {
        if ($this->plannings->removeElement($planning)) {
            // set the owning side to null (unless already changed)
            if ($planning->getUser() === $this) {
                $planning->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reponsereclamation>
     */
    public function getReponsereclamations(): Collection
    {
        return $this->reponsereclamations;
    }

    public function addReponsereclamation(Reponsereclamation $reponsereclamation): static
    {
        if (!$this->reponsereclamations->contains($reponsereclamation)) {
            $this->reponsereclamations->add($reponsereclamation);
            $reponsereclamation->setAdmin($this);
        }

        return $this;
    }

    public function removeReponsereclamation(Reponsereclamation $reponsereclamation): static
    {
        if ($this->reponsereclamations->removeElement($reponsereclamation)) {
            // set the owning side to null (unless already changed)
            if ($reponsereclamation->getAdmin() === $this) {
                $reponsereclamation->setAdmin(null);
            }
        }

        return $this;
    }
}