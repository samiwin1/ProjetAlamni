<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;
use App\Security\CustomAuthenticator;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;


#[Route('/conversationBack')]
class ConversationBackController extends AbstractController

    {

        
        #[Route('/', name: 'conversation_index1', methods: ['GET'])]
        public function index(
            Request $request, 
            EntityManagerInterface $entityManager, 
            ConversationRepository $conversationRepository, 
            UserRepository $userRepository
        ): Response {
            // ✅ Check user role directly without injecting Security
            if ($this->isGranted('ROLE_ELEVE')) {
                return $this->redirectToRoute('app_home'); // Redirect to home if user is ROLE_ELEVE
            }
        
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->redirectToRoute('app_login');
            }
        
            // Fetch all conversations where the current user is user1 or user2
            $conversations = $conversationRepository->createQueryBuilder('c')
                ->where('c.user1 = :user OR c.user2 = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        
            return $this->render('conversation_back/index.html.twig', [
                'conversations' => $conversations,
                'currentUser' => $user,
            ]);
        }
            
        #[Route('/all', name: 'conversation_admin_index', methods: ['GET'])]
        public function showAllForAdmin(
            EntityManagerInterface $entityManager,
            ConversationRepository $conversationRepository
        ): Response {
            // ✅ Ensure only ADMIN can access this
            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_home');
            }
        
            // ✅ Fetch all conversations from the repository
            $conversations = $conversationRepository->findAll();
        
            // Render the conversation list for admin
            return $this->render('conversation_back/index.html.twig', [
                'conversations' => $conversations,
            ]);
        }
        

        #[Route('/create', name: 'conversation_create', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $entityManager): Response
{
    $session = $request->getSession();
    $lastUsername = $session->get('_security.last_username');

    if (!$lastUsername) {
        return $this->redirectToRoute('app_login');
    }

    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $lastUsername]);

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Get the selected user ID from the request
    $selectedUserId = $request->request->get('user_id');
    $otherUser = $entityManager->getRepository(User::class)->find($selectedUserId);

    if (!$otherUser) {
        return new Response("Utilisateur non trouvé", 404);
    }

    // Check if a conversation already exists
    $existingConversation = $entityManager->getRepository(Conversation::class)->findOneBy([
        'user1' => $user,
        'user2' => $otherUser
    ]);

    if ($existingConversation) {
        return new Response("Conversation déjà existante !");
    }

    $conversation = new Conversation();
    $conversation->setUser1($user);
    $conversation->setUser2($otherUser);
    $conversation->setCreatedAt(new \DateTime());

    $entityManager->persist($conversation);
    $entityManager->flush();

    return $this->redirectToRoute('conversation_index');
}

                
#[Route('/new/{userId}', name: 'conversationAdmin_new', methods: ['GET', 'POST'])]
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
                // return $this->redirect($request->headers->get('referer'));
                return $this->redirectToRoute('app_message_show1', ['id' => $conversation->getId()]);

            }
        }

        // If form submission fails, show an error
        $this->addFlash('error', 'Invalid recipient or message content.');
    }

    return $this->render('messages_back/new.html.twig', [
        'users' => $users,
    ]);
}


#[Route('/messages/{id}', name: 'app_message_show1', methods: ['GET', 'POST'])]
public function show(
    Request $request, 
    Conversation $conversation, 
    MessageRepository $messageRepository, 
    EntityManagerInterface $entityManager
): Response {
    $messages = $messageRepository->findBy(['conversation' => $conversation], ['createdAt' => 'ASC']);

    // ✅ Create a new message form
    $message = new Message();
    $form = $this->createForm(MessageType::class, $message);
    $form->handleRequest($request);

    // ✅ Handle form submission
    if ($form->isSubmitted() && $form->isValid()) {
        $message->setSender($this->getUser());
        $message->setConversation($conversation);
        $message->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

        return $this->redirectToRoute('app_message_show1', ['id' => $conversation->getId()]);
    }

    // ✅ Pass the form and messages to the template
    return $this->render('messages_back/show.html.twig', [
        'conversation' => $conversation,
        'messages' => $messages,
        'form' => $form->createView(), // ✅ Ensure form is available in Twig
    ]);
}

#[Route('/message/{id}/edit', name: 'app_message_editAdmin', methods: ['POST'])]
public function editMessage(
    Request $request, 
    Message $message, 
    EntityManagerInterface $entityManager
): Response {
    // Vérifier que l'utilisateur est bien le propriétaire du message
    if ($message->getSender() !== $this->getUser()) {
        $this->addFlash('error', "Vous ne pouvez modifier que vos propres messages.");
        return $this->redirectToRoute('app_message_show1', ['id' => $message->getConversation()->getId()]);
    }

    // Récupérer le contenu du message depuis la requête
    $newContent = $request->request->get('content');

    // Vérifier que le message n'est pas vide ou ne contient que des espaces
    if (empty(trim($newContent))) {
        $this->addFlash('error', "Le message ne peut pas être vide.");
        return $this->redirectToRoute('app_message_show1', ['id' => $message->getConversation()->getId()]);
    }

    // Mettre à jour le contenu du message
    $message->setContent($newContent);
    $entityManager->flush();

    // Ajouter un message de succès
    $this->addFlash('success', "Message modifié avec succès.");

    // Rediriger vers la conversation après modification
    return $this->redirectToRoute('app_message_show1', ['id' => $message->getConversation()->getId()]);
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
    #[Route('/message/send/{id}', name: 'app_message_send', methods: ['POST'])]
    public function sendMessage(Request $request, Conversation $conversation, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $message->setSender($this->getUser());
                $message->setConversation($conversation);
                $message->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($message);
                $entityManager->flush();
    
                return $this->redirectToRoute('app_message_show1', ['id' => $conversation->getId()]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi du message. Vérifiez votre saisie.');
            }
        }
    
        return $this->render('messages_back/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $conversation->getMessages(),
            'form' => $form->createView(), 
        ]);
    }
    #[Route('/{id}/delete', name: 'app_message_deleteAdmin', methods: ['POST'])]
    public function deleteMessage(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        // Get the conversation before deleting the message
        $conversation = $message->getConversation();
    
        if (!$conversation) {
            throw $this->createNotFoundException('Conversation not found.');
        }
    
        // CSRF Token Verification
        if ($this->isCsrfTokenValid('delete' . $message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
        }
    
        // Redirect back to the conversation after deleting the message
        return $this->redirectToRoute('app_message_show1', ['id' => $conversation->getId()], Response::HTTP_SEE_OTHER);
}
    }