<?php

namespace App\Entity;

use App\Repository\ReponsereclamationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponsereclamationRepository::class)]
class Reponsereclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: "reclamation_id_id", referencedColumnName: "id", nullable: false)] // ✅ Match DB column name
    private ?Reclamation $reclamation = null;


    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reponsereclamations')]
    #[ORM\JoinColumn(name: "admin_id_id", referencedColumnName: "id", nullable: false)]
    private ?User $admin = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenue = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_reponse = null;

    public function __construct()
    {
        $this->date_reponse = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getReclamation(): ?Reclamation { return $this->reclamation; }
    public function setReclamation(?Reclamation $reclamation): self { $this->reclamation = $reclamation; return $this; }

    public function getAdmin(): ?User { return $this->admin; }
    public function setAdmin(?User $admin): self { $this->admin = $admin; return $this; }

    public function getContenue(): ?string { return $this->contenue; }
    public function setContenue(string $contenue): self { $this->contenue = $contenue; return $this; }

    public function getDateReponse(): ?\DateTimeInterface { return $this->date_reponse; }
    public function setDateReponse(\DateTimeInterface $date_reponse): self { $this->date_reponse = $date_reponse; return $this; }
}
