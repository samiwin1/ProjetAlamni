<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_inscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_niveau = null;

#[ORM\Column(length: 64, nullable: true)]
    private ?string $resetToken = null;


    /**
     * @var Collection<int, Planning>
     */
    #[ORM\OneToMany(targetEntity: Planning::class, mappedBy: 'user')]
    private Collection $plannings;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Rating::class, orphanRemoval: true)]
    private Collection $ratings;
    

    public function __construct()
    {
        
        $this->plannings = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->ratings = new ArrayCollection();
       
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    // Méthodes de compatibilité pour l'ancien système
    public function getRole(): ?string
    {
        return !empty($this->roles) ? $this->roles[0] : 'ROLE_USER';
    }

    public function setRole(string $role): self
    {
        $this->roles = [$role];
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // Méthodes de compatibilité
    public function getMotDePasse(): ?string
    {
        return $this->password;
    }

    public function setMotDePasse(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
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
            if ($planning->getUser() === $this) {
                $planning->setUser(null);
            }
        }

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
            $rating->setUser($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
            }
        }

        return $this;
    }

  
}
