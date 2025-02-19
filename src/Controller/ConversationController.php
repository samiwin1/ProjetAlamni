<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/conversation')]


class ConversationController extends AbstractController
{
    #[Route('/', name: 'app_conversation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $conversations = $entityManager->getRepository(Conversation::class)->findAll();

        return $this->render('conversation/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }


    #[Route('/new', name: 'app_conversation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            // Redirection si l'utilisateur n'est pas connecté
           // return $this->redirectToRoute('app_login');
        }
    
        $conversation = new Conversation();
        $conversation->addParticipant($user);
    
        if ($request->isMethod('POST')) {
            $otherUserId = $request->request->get('user_id');
            if (!$otherUserId) {
                $this->addFlash('error', 'Veuillez sélectionner un utilisateur.');
                return $this->redirectToRoute('app_conversation_new');
            }
    
            $otherUser = $entityManager->getRepository(User::class)->find($otherUserId);
            if (!$otherUser) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_conversation_new');
            }
    
            // Ajouter l'autre utilisateur et sauvegarder la conversation
            $conversation->addParticipant($otherUser);
            $entityManager->persist($conversation);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_conversation_show', [
                'id' => $conversation->getId(),
            ]);
        }
    
        return $this->render('conversation/new.html.twig', [
            'conversation' => $conversation,
        ]);
    }
    

    #[Route('/{id}', name: 'app_conversation_show', methods: ['GET', 'POST'])]
    public function show(Conversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();
        if ($request->isMethod('POST')) {
            $message->setContent($request->request->get('content'));
            $message->setUser($this->getUser());
            $message->setConversation($conversation);
            $entityManager->persist($message);
            $entityManager->flush();
        }

        return $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $conversation->getMessages(),
        ]);
    }
}
 