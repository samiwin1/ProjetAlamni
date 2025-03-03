<?php
namespace App\MessagePinBundle\Controller;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/message')]
class PinnedMessageController extends AbstractController
{
    #[Route('/pin/{id}', name: 'message_pin', methods: ['POST'])]
    public function pinMessage(Message $message, EntityManagerInterface $entityManager): Response
    {
        if (!$message) {
            return new Response("Message non trouvé", 404);
        }

        $message->setPinned(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
    }

    #[Route('/unpin/{id}', name: 'message_unpin', methods: ['POST'])]
    public function unpinMessage(Message $message, EntityManagerInterface $entityManager): Response
    {
        if (!$message) {
            return new Response("Message non trouvé", 404);
        }

        $message->setPinned(false);
        $entityManager->flush();

        return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
    }
}
