<?php
// src/Entity/LikeDislike.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LikeDislike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Message $message;

    #[ORM\Column(type: 'boolean')]
    private bool $isLiked;

    // ✅ GETTERS & SETTERS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function isLiked(): bool
    {
        return $this->isLiked;
    }

  
    

public function getIsLiked(): bool
{
    return $this->isLiked;
}

public function setIsLiked(bool $isLiked): self
{
    $this->isLiked = $isLiked;
    return $this;
}

}
