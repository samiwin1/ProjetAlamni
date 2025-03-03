<?php
// src/Entity/Message.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\LikeDislike;  // Add this line to reference the LikeDislike entity

#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $sender;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne]
    private ?User $rec_id = null;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: LikeDislike::class)]
    private Collection $likeDislikes;

    public function __construct()
    {
        $this->likeDislikes = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
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

    public function getRecId(): ?User
    {
        return $this->rec_id;
    }

    public function setRecId(?User $rec_id): self
    {
        $this->rec_id = $rec_id;
        return $this;
    }

    public function getLikeDislikes(): Collection
    {
        return $this->likeDislikes;
    }

    public function addLikeDislike(LikeDislike $likeDislike): self
    {
        if (!$this->likeDislikes->contains($likeDislike)) {
            $this->likeDislikes[] = $likeDislike;
            $likeDislike->setMessage($this);
        }

        return $this;
    }

    public function removeLikeDislike(LikeDislike $likeDislike): self
    {
        if ($this->likeDislikes->removeElement($likeDislike)) {
            if ($likeDislike->getMessage() === $this) {
                $likeDislike->setMessage(null);
            }
        }

        return $this;
    }

    public function getLikesCount(): int
    {
        return count(array_filter($this->likeDislikes->toArray(), fn($ld) => $ld->isLiked()));
    }

    public function getDislikesCount(): int
    {
        return count(array_filter($this->likeDislikes->toArray(), fn($ld) => !$ld->isLiked()));
    }
   


#[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPinned = false;

    public function getIsPinned(): bool
    {
        return $this->isPinned;
    }

    public function setPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;
        return $this;
    }
}
