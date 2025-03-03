<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user2;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['remove'], orphanRemoval: true)]
    private Collection $messages;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: "favoriteConversations")]
    #[ORM\JoinTable(name: "conversation_favorites")]
    private Collection $favoritedBy;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->messages = new ArrayCollection();
        $this->favoritedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser1(): ?User
    {
        return $this->user1;
    }

    public function setUser1(User $user1): self
    {
        $this->user1 = $user1;
        return $this;
    }

    public function getUser2(): ?User
    {
        return $this->user2;
    }

    public function setUser2(User $user2): self
    {
        $this->user2 = $user2;
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

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }
        return $this;
    }

    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    public function isFavoritedBy(User $user): bool
    {
        return $this->favoritedBy->contains($user);
    }

    public function addFavorite(User $user): void
    {
        if (!$this->favoritedBy->contains($user)) {
            $this->favoritedBy->add($user);
        }
    }

    public function removeFavorite(User $user): void
    {
        $this->favoritedBy->removeElement($user);
    }
}
