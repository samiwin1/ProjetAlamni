<?php

namespace App\Controller;


use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Entity\Reclamation;

use App\Entity\Reponsereclamation;
use App\Form\ReponsereclamationType;
use App\Repository\ReponsereclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


#[Route('/reponsereclamation')]
final class ReponsereclamationController extends AbstractController{
    #[Route(name: 'app_reponsereclamation_index', methods: ['GET'])]
    public function index(ReponsereclamationRepository $reponsereclamationRepository): Response
    {
        return $this->render('reponsereclamation/index.html.twig', [
            'reponsereclamations' => $reponsereclamationRepository->findAll(),
        ]);
    }

    public function getUnreadResponsesCount(SessionInterface $session, ReponsereclamationRepository $reponsereclamationRepository): int
    {
        $userEmail = $session->get('user_email');
        if (!$userEmail) {
            return 0; // Aucun utilisateur connecté
        }
        
        return $reponsereclamationRepository->countUnreadResponsesForUser($userEmail);
    }
   

    #[Route('/new/{reclamationId}', name: 'app_reponsereclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, int $reclamationId): Response
    {
        // 🔹 Fetch the logged-in user from session
        $session = $request->getSession();
        $userEmail = $session->get('user_email');
    
        // 🔹 Verify if the user is logged in
        if (!$userEmail) {
            $this->addFlash('error', 'Veuillez vous connecter pour répondre aux réclamations.');
            return $this->redirectToRoute('login');
        }
    
        // 🔹 Fetch the user from database
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
    
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('login');
        }
    
        // 🔹 Fetch the reclamation
        $reclamation = $entityManager->getRepository(Reclamation::class)->find($reclamationId);
        if (!$reclamation) {
            $this->addFlash('error', 'Réclamation non trouvée.');
            return $this->redirectToRoute('app_reclamation_index');
        }
    
        // 🔹 Create new response
        $reponsereclamation = new Reponsereclamation();
        $reponsereclamation->setReclamation($reclamation);
        $reponsereclamation->setAdmin($user); // Set the currently logged-in user
        $reponsereclamation->setIsRead(false); // 🚨 Marquer la réponse comme non lue


        $reclamation->setStatus('Résolue'); 
        
        $form = $this->createForm(ReponsereclamationType::class, $reponsereclamation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponsereclamation);
            $entityManager->flush();
    
            $this->addFlash('success', 'Réponse envoyée avec succès.');
            return $this->redirectToRoute('app_reclamation_front_show', ['id' => $reclamationId]);
        }
    
        return $this->render('reponsereclamation/new.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }
    
    
    // #[Route('/reponse/{id}', name: 'app_reponsereclamation_show', methods: ['GET'])]
    // public function show(Reponsereclamation $reponsereclamation, EntityManagerInterface $entityManager): Response
    // {
    //     $reclamation = $reponsereclamation->getReclamation(); // ✅ Get the reclamation
    
    //     // 🟢 **Mise à jour du statut de la réclamation à "En cours"**
    //     if ($reclamation->getStatus() === 'En attente') {
    //         $reclamation->setStatus('En cours');
    //         $reponsereclamation->setIsRead(true); // ✅ Marquer comme lue
    //         $entityManager->flush();
            
    //     }
    
    //     return $this->render('reponsereclamation/show.html.twig', [
    //         'reponsereclamation' => $reponsereclamation,
    //         'reclamation' => $reclamation, // ✅ Pass reclamation to the template
    //     ]);
    // }
    #[Route('/reponse/{id}', name: 'app_reponsereclamation_show', methods: ['GET'])]
    public function show(
        Reponsereclamation $reponsereclamation,
        EntityManagerInterface $entityManager,
        ReponsereclamationRepository $reponsereclamationRepository,
        SessionInterface $session
    ): Response {
        $reclamation = $reponsereclamation->getReclamation();
    
        // 🟢 Récupérer toutes les réponses de la réclamation
        $allResponses = $reponsereclamationRepository->findBy(['reclamation' => $reclamation]);
    
        // 🟢 Marquer toutes les réponses comme "lues"
        $updated = false;
        foreach ($allResponses as $response) {
            if (!$response->isRead()) {
                $response->setIsRead(true);
                $updated = true;
            }
        }
    
        if ($updated) {
            $entityManager->flush(); // ✅ Mise à jour en base de données
        }
    
        // 🔹 Mettre à jour le nombre de notifications non lues
        $unreadResponsesCount = $reponsereclamationRepository->countUnreadResponsesForUser($session->get('user_email'));
    
        return $this->render('reponsereclamation/show.html.twig', [
            'reponsereclamation' => $reponsereclamation,
            'reclamation' => $reclamation,
            'unreadResponsesCount' => $unreadResponsesCount, 
        ]);
    }
    
    

    #[Route('/{id}/edit', name: 'app_reponsereclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponsereclamation $reponsereclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponsereclamationType::class, $reponsereclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reponsereclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponsereclamation/edit.html.twig', [
            'reponsereclamation' => $reponsereclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reponsereclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reponsereclamation $reponsereclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponsereclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponsereclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponsereclamation_index', [], Response::HTTP_SEE_OTHER);
    }



}
