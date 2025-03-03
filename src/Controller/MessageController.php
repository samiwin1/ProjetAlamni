<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\LikeDislike;
use App\Entity\Conversation;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;

#[Route('/message')]
final class MessageController extends AbstractController
{
    #[Route(name: 'app_message_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $messages = $entityManager->getRepository(Message::class)->findAll();

        return $this->render('message/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/new', name: 'app_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('app_message_index');
        }

        return $this->render('message/new.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_message_edit', methods: ['POST'])]
    public function edit(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($message->getSender() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres messages.');
        }

        if (!$this->isCsrfTokenValid('edit' . $message->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedException('Token CSRF invalide.');
        }

        $newContent = $request->request->get('content');

        if (!empty($newContent)) {
            $message->setContent($newContent);
            $entityManager->flush();
            $this->addFlash('success', 'Message modifié avec succès.');
        } else {
            $this->addFlash('warning', 'Le message ne peut pas être vide.');
        }

        return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
    }

   

    

    #[Route('/{id}/pin', name: 'message_pin', methods: ['POST'])]
    public function pinMessage(int $id, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé.');
        }

        $message->setPinned(true);
        $entityManager->flush();

        $this->addFlash('success', 'Message épinglé avec succès.');
        return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
    }

    #[Route('/{id}/unpin', name: 'message_unpin', methods: ['POST'])]
    public function unpinMessage(int $id, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé.');
        }

        $message->setPinned(false);
        $entityManager->flush();

        $this->addFlash('success', 'Message désépinglé avec succès.');
        return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
    }

  

 
    #[Route('/{id}/like', name: 'app_message_like', methods: ['POST'])]
public function like(int $id, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    $message = $entityManager->getRepository(Message::class)->find($id);

    if (!$message) {
        throw $this->createNotFoundException('Message non trouvé.');
    }

    $likeDislikeRepository = $entityManager->getRepository(LikeDislike::class);
    $existingReaction = $likeDislikeRepository->findOneBy(['user' => $user, 'message' => $message]);

    if ($existingReaction) {
        if ($existingReaction->getIsLiked() === 1) {
            // Remove like (toggle off)
            $entityManager->remove($existingReaction);
        } else {
            // Change dislike to like
            $existingReaction->setIsLiked(1);
        }
    } else {
        // New like
        $likeDislike = new LikeDislike();
        $likeDislike->setUser($user);
        $likeDislike->setMessage($message);
        $likeDislike->setIsLiked(1); // 1 for like

        $entityManager->persist($likeDislike);
    }

    $entityManager->flush();

    return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
}

#[Route('/{id}/dislike', name: 'app_message_dislike', methods: ['POST'])]
public function dislike(int $id, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    $message = $entityManager->getRepository(Message::class)->find($id);

    if (!$message) {
        throw $this->createNotFoundException('Message non trouvé.');
    }

    $likeDislikeRepository = $entityManager->getRepository(LikeDislike::class);
    $existingReaction = $likeDislikeRepository->findOneBy(['user' => $user, 'message' => $message]);

    if ($existingReaction) {
        if ($existingReaction->getIsLiked() === 0) {
            // Remove dislike (toggle off)
            $entityManager->remove($existingReaction);
        } else {
            // Change like to dislike
            $existingReaction->setIsLiked(0);
        }
    } else {
        // New dislike
        $likeDislike = new LikeDislike();
        $likeDislike->setUser($user);
        $likeDislike->setMessage($message);
        $likeDislike->setIsLiked(0); // 0 for dislike

        $entityManager->persist($likeDislike);
    }

    $entityManager->flush();

    return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()]);
}
#[Route('/messages/{id}', name: 'app_message_show', methods: ['GET', 'POST'])]
public function show(
    int $id,
    Request $request,
    EntityManagerInterface $entityManager,
    ConversationRepository $conversationRepository,
    MessageRepository $messageRepository
): Response {
    $session = $request->getSession();
    $lastUsername = $session->get('_security.last_username');

    if (!$lastUsername) {
        return $this->redirectToRoute('app_login');
    }

    $currentUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $lastUsername]);
    if (!$currentUser) {
        return $this->redirectToRoute('app_login');
    }

    $conversation = $conversationRepository->find($id);
    if (!$conversation) {
        throw $this->createNotFoundException('Conversation non trouvée.');
    }

    if ($conversation->getUser1() !== $currentUser && $conversation->getUser2() !== $currentUser) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette conversation.');
    }

    // Fetch messages
    $messages = $messageRepository->findBy(
        ['conversation' => $conversation],
        ['createdAt' => 'ASC']
    );

    // Fetch pinned messages (Ensure it's always an array)
    $pinnedMessages = $messageRepository->findBy(
        ['conversation' => $conversation, 'isPinned' => true],
        ['createdAt' => 'ASC']
    ) ?? [];

    // New message form
    $newMessage = new Message();
    $form = $this->createForm(MessageType::class, $newMessage);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $newMessage->setSender($currentUser);
        $newMessage->setConversation($conversation);
        $newMessage->setCreatedAt(new \DateTime());
        $newMessage->setRecId(
            $conversation->getUser1() === $currentUser ? $conversation->getUser2() : $conversation->getUser1()
        );

        $entityManager->persist($newMessage);
        $entityManager->flush();

        return $this->redirectToRoute('app_message_show', ['id' => $conversation->getId()], Response::HTTP_SEE_OTHER);
    }

    return $this->render('message/show.html.twig', [
        'conversation' => $conversation,
        'messages' => $messages,
        'pinnedMessages' => $pinnedMessages,
        'form' => $form->createView(),
    ]);
}
#[Route('/{id}/delete', name: 'app_message_delete', methods: ['POST'])]
public function delete(Request $request, Message $message, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();

    if ($message->getSender() !== $user) {
        throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres messages.');
    }

    // ✅ Delete all likes/dislikes related to this message
    $likeDislikeRepository = $entityManager->getRepository(LikeDislike::class);
    $likesDislikes = $likeDislikeRepository->findBy(['message' => $message]);

    foreach ($likesDislikes as $likeDislike) {
        $entityManager->remove($likeDislike);
    }
    $entityManager->flush(); // ✅ Flush before deleting the message

    // ✅ Now delete the message
    if ($this->isCsrfTokenValid('delete' . $message->getId(), $request->request->get('_token'))) {
        $entityManager->remove($message);
        $entityManager->flush();
        $this->addFlash('success', 'Message supprimé avec succès.');
    } else {
        $this->addFlash('danger', 'Échec de la suppression du message.');
    }

    return $this->redirectToRoute('app_message_show', ['id' => $message->getConversation()->getId()], Response::HTTP_SEE_OTHER);
}


}
