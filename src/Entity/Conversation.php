<?php

namespace App\Entity;

use App\Form\UserType;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ConversationRepository;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: UserType::class)]
    #[ORM\JoinTable(name: 'conversation_user')]
    private Collection $participants;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['remove'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(UserType $user): self
    {
        if (!$this->participants->contains($user)) {
            $this->participants[] = $user;
        }
        return $this;
    }

    public function removeParticipant(UserType $user): self
    {
        $this->participants->removeElement($user);
        return $this;
    }

    public function getMessages(): Collection { return $this->messages; }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }
        return $this;
    }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}
