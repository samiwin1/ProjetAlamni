<?php

namespace App\FavoriteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\FavoriteBundle\Entity\FavoriteConversation;
use App\Entity\Conversation;
use App\Entity\User;

#[Route('/favorites')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(Conversation $conversation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Check if this conversation is already favorited
        $favoriteRepo = $entityManager->getRepository(FavoriteConversation::class);
        $existingFavorite = $favoriteRepo->findOneBy(['user' => $user, 'conversation' => $conversation]);

        if ($existingFavorite) {
            // Remove from favorites
            $entityManager->remove($existingFavorite);
            $entityManager->flush();
            $this->addFlash('success', 'Conversation removed from favorites.');
        } else {
            // Add to favorites
            $favorite = new FavoriteConversation();
            $favorite->setUser($user);
            $favorite->setConversation($conversation);
            $entityManager->persist($favorite);
            $entityManager->flush();
            $this->addFlash('success', 'Conversation added to favorites.');
        }

        return $this->redirectToRoute('conversation_index');
    }

    #[Route('/', name: 'favorite_list', methods: ['GET'])]
    public function listFavorites(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $entityManager->getRepository(FavoriteConversation::class)->findBy(['user' => $user]);

        return $this->render('@Favorite/favorite_list.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}
