<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Entity\FavoriteConversation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use App\Repository\FavoriteConversationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/conversation')]
class ConversationController extends AbstractController
{
    #[Route('/conversations', name: 'conversation_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ConversationRepository $conversationRepository,
        FavoriteConversationRepository $favoriteRepo,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch all conversations where the user is involved
        $conversations = $conversationRepository->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Get favorite status for each conversation
        $conversationsWithFavorites = [];
        foreach ($conversations as $conversation) {
            $isFavorite = $favoriteRepo->findOneBy([
                'user' => $user,
                'conversation' => $conversation
            ]) !== null;

            $conversationsWithFavorites[] = [
                'conversation' => $conversation,
                'isFavorite' => $isFavorite,
            ];
        }

        return $this->render('conversation/index.html.twig', [
            'conversationsWithFavorites' => $conversationsWithFavorites,
            'currentUser' => $user,
        ]);
    }
  
    
    #[Route('/{id}/favorite', name: 'toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(
        int $id,
        EntityManagerInterface $entityManager,
        FavoriteConversationRepository $favoriteRepo,
        ConversationRepository $conversationRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $conversation = $conversationRepo->find($id);
        if (!$conversation) {
            throw $this->createNotFoundException('Conversation not found.');
        }

        // Check if conversation is already favorited
        $existingFavorite = $favoriteRepo->findOneBy([
            'user' => $user,
            'conversation' => $conversation
        ]);

        if ($existingFavorite) {
            $entityManager->remove($existingFavorite);
        } else {
            $favorite = new FavoriteConversation();
            $favorite->setUser($user);
            $favorite->setConversation($conversation);
            $entityManager->persist($favorite);
        }

        $entityManager->flush();

        return $this->redirectToRoute('conversation_index');
    }

  
    #[Route('/new/{userId}', name: 'conversation_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    int $userId, 
    EntityManagerInterface $entityManager, 
    UserRepository $userRepository, 
    ConversationRepository $conversationRepository
): Response {
    // Fetch the logged-in user (sender)
    $session = $request->getSession();
    $lastUsername = $session->get('_security.last_username');

    if (!$lastUsername) {
        return $this->redirectToRoute('app_login'); // Redirect if not logged in
    }

    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $lastUsername]);
    if (!$user) {
        return $this->redirectToRoute('app_login'); // Redirect if user not found
    }

    // Fetch all users except the logged-in user
    $users = $userRepository->createQueryBuilder('u')
        ->where('u.id != :userId')
        ->setParameter('userId', $user->getId()) // Ensure it excludes the logged-in user
        ->getQuery()
        ->getResult();

    // Handle form submission
    if ($request->isMethod('POST')) {
        $recipientId = $request->request->get('recipient');
        $messageContent = $request->request->get('message');

        if ($recipientId && $messageContent) {
            $recipient = $userRepository->find($recipientId);

            if ($recipient) {
                // ✅ Ensure conversation is always created
                $conversation = $conversationRepository->findOneBy([
                    'user1' => $user,
                    'user2' => $recipient
                ]) ?? $conversationRepository->findOneBy([
                    'user1' => $recipient,
                    'user2' => $user
                ]);

                if (!$conversation) {
                    $conversation = new Conversation();
                    $conversation->setUser1($user);
                    $conversation->setUser2($recipient);
                    $conversation->setCreatedAt(new \DateTime());

                    $entityManager->persist($conversation);
                    $entityManager->flush(); // 🔹 Make sure conversation is saved before creating the message
                }

                // ✅ Now create the first message
                $message = new Message();
                $message->setSender($user);
                $message->setRecId($recipient);
                $message->setConversation($conversation);
                $message->setContent($messageContent);
                $message->setCreatedAt(new \DateTime());

                $entityManager->persist($message);
                $entityManager->flush();

                // Redirect to the conversation messages
                return $this->redirectToRoute('app_message_show', ['id' => $conversation->getId()]);
            }
        }

        // If form submission fails, show an error
        $this->addFlash('error', 'Invalid recipient or message content.');
    }

    return $this->render('conversation/new.html.twig', [
        'users' => $users,
    ]);
}

    #[Route('/{id}/delete', name: 'conversation_delete', methods: ['POST'])]
    public function delete(Request $request, Conversation $conversation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user)) {
            return new Response("Accès refusé", 403);
        }

        $entityManager->remove($conversation);
        $entityManager->flush();

        return $this->redirectToRoute('conversation_index');
    }

    #[Route('/list', name: 'conversation_list', methods: ['GET'])]
    public function listConversations(
        Request $request, 
        EntityManagerInterface $entityManager, 
        ConversationRepository $conversationRepository
    ): Response {
        $session = $request->getSession();
        $lastUsername = $session->get('_security.last_username'); // Retrieve last username

        // Check if last username exists
        if (!$lastUsername) {
            return $this->redirectToRoute('app_login'); // Redirect if not logged in
        }

        // Fetch user by email or username
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $lastUsername]);

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Redirect if user not found
        }

        $userId = $user->getId(); // Get user ID

        // Fetch all conversations for the user
        $conversations = $conversationRepository->createQueryBuilder('c')
            ->where('c.user1_id = :userId OR c.user2_id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return $this->render('conversation/list.html.twig', [
            'conversations' => $conversations,
        ]);
    }
    #[Route('/favorites', name: 'conversation_favorites', methods: ['GET'])]
public function favoriteConversations(
    EntityManagerInterface $entityManager, 
    ConversationRepository $conversationRepository
): Response {
    $user = $this->getUser();
    if (!$user instanceof User) {
        return $this->redirectToRoute('app_login');
    }

    $favorites = $entityManager->getRepository(FavoriteConversation::class)->findBy(['user' => $user]);
    $favoriteConversations = array_map(fn($favorite) => $favorite->getConversation(), $favorites);

    return $this->render('conversation/index.html.twig', [
        'conversationsWithFavorites' => array_map(fn($conv) => ['conversation' => $conv, 'isFavorite' => true], $favoriteConversations),
        'currentUser' => $user,
    ]);
}

}
