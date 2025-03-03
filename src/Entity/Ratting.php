<?php

namespace App\Entity;

use App\Repository\RattingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RattingRepository::class)]
class Ratting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'rattings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private ?int $ratting = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, event>
     */
    #[ORM\OneToMany(targetEntity: event::class, mappedBy: 'ratting')]
    private Collection $event_id;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->event_id = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRatting(): ?int
    {
        return $this->ratting;
    }

    public function setRatting(?int $ratting): self
{
    $this->ratting = $ratting;
    return $this;
}

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, event>
     */
    public function getEventId(): Collection
    {
        return $this->event_id;
    }

    public function addEventId(event $eventId): static
    {
        if (!$this->event_id->contains($eventId)) {
            $this->event_id->add($eventId);
            $eventId->setRatting($this);
        }

        return $this;
    }

    public function removeEventId(event $eventId): static
    {
        if ($this->event_id->removeElement($eventId)) {
            // set the owning side to null (unless already changed)
            if ($eventId->getRatting() === $this) {
                $eventId->setRatting(null);
            }
        }

        return $this;
    }
}
