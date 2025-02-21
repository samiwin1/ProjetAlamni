<?php

namespace App\Controller;


use App\Repository\ReponsereclamationRepository;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/')]
final class ReclamationController extends AbstractController
{
    #[Route('/reclam', name: 'app_reclamation', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository, ): Response
    {
        $stats = [
            'En attente' => $reclamationRepository->count(['status' => 'En attente']),
            'En cours' => $reclamationRepository->count(['status' => 'En cours']),
            'Résolue' => $reclamationRepository->count(['status' => 'Résolue']),
        ];

      

        return $this->render('frontOffice/reclamation.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
            'stats' => $stats,
           
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
   
   

    #[Route('/reclamation/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, ReponsereclamationRepository $reponsereclamationRepository, ): Response
{
    // Récupérer l'email de l'utilisateur depuis la session
    $session = $request->getSession();
    $userEmail = $session->get('user_email');

    // Vérifier si un email est en session
    if (!$userEmail) {
        $this->addFlash('error', 'Veuillez vous connecter pour envoyer une réclamation.');
        return $this->redirectToRoute('login');
    }

    // Récupérer l'utilisateur depuis la base de données
    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

    if (!$user) {
        $this->addFlash('error', 'Utilisateur non trouvé.');
        return $this->redirectToRoute('login');
    }

    // Création d'une nouvelle réclamation
    $reclamation = new Reclamation();
    $reclamation->setUser($user);
    $reclamation->setUserEmail($user->getEmail());
    $reclamation->setRole($user->getRole()); // Importer le rôle depuis la base de données
    $reclamation->setStatus('En attente');

    // Création du formulaire
    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    // Gestion des erreurs
    $errors = [];

    if ($form->isSubmitted()) {
        if (!$reclamation->getUserEmail()) {
            $errors[] = "L'email utilisateur est obligatoire.";
        } elseif (!filter_var($reclamation->getUserEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email utilisateur n'est pas valide.";
        }

        if (!$reclamation->getAdminMail()) {
            $errors[] = "Le destinataire est obligatoire.";
        } elseif (!filter_var($reclamation->getAdminMail(), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email destinataire n'est pas valide.";
        }

        if (!$reclamation->getRole()) {
            $errors[] = "Veuillez sélectionner un rôle.";
        }

        if (!$reclamation->getObjet() || strlen($reclamation->getObjet()) < 5) {
            $errors[] = "L'objet doit contenir au moins 5 caractères.";
        }

        if (!$reclamation->getDescription() || strlen($reclamation->getDescription()) < 10) {
            $errors[] = "La description doit contenir au moins 10 caractères.";
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        } else {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été envoyée avec succès.');
            return $this->redirectToRoute('app_reclamation_new');
        }
    }

    $unreadResponsesCount = $this->getUnreadResponsesCount($session, $reponsereclamationRepository);



    return $this->render('frontOffice/reclamation.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
        'unreadResponsesCount' => $unreadResponsesCount, 
    ]);
}

// }
    

    #[Route('/{id}/delete', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Échec de la suppression. Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_reclamation'); // Redirects to the reclamations list after deletion
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Ensure all required fields are filled
                if (!$reclamation->getUserEmail() || !$reclamation->getAdminMail() || !$reclamation->getRole() || !$reclamation->getObjet() || !$reclamation->getDescription()) {
                    $this->addFlash('error', 'Tous les champs sont obligatoires.');
                } else {
                    $entityManager->flush();
                    $this->addFlash('success', 'Réclamation mise à jour avec succès !');
                    return $this->redirectToRoute('app_reclamation_front_show', ['id' => $reclamation->getId()]);
                }
            } else {
                $this->addFlash('error', 'Échec de la mise à jour. Veuillez vérifier vos informations.');
            }
        }

        return $this->render('reclamation/edit.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}', name: 'app_reclamation_front_show', methods: ['GET'])]
    public function show(Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les réponses associées à cette réclamation
        $reponses = $reclamation->getReponses();
    
        // 🟢 Vérifier le statut et le mettre à jour
        if ($reclamation->getStatus() === 'En attente') {
            $reclamation->setStatus('En cours');
        }
    
        // 🟢 Si la réclamation a au moins une réponse, on la marque comme "Résolue"
        if (!$reponses->isEmpty()) {
            $reclamation->setStatus('Résolue');
        }
    
        $entityManager->flush(); // Sauvegarde en base
    
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
    

    #[Route('/my-reclamations', name: 'app_my_reclamations', methods: ['GET'])]
    public function myReclamations(
        ReclamationRepository $reclamationRepository, 
        ReponsereclamationRepository $reponsereclamationRepository, 
        Request $request
    ): Response {
        $session = $request->getSession();
        $userEmail = $session->get('user_email');
    
        if (!$userEmail) {
            $this->addFlash('error', 'Veuillez vous connecter pour voir vos réclamations.');
            return $this->redirectToRoute('login');
        }
    
        $reclamations = $reclamationRepository->findBy(['user_email' => $userEmail], ['date_soumission' => 'DESC']);
        $reponses = [];
        $unreadResponsesCounts = []; // 🔹 Stocke le nombre de réponses non lues par réclamation
    
        foreach ($reclamations as $reclamation) {
            $response = $reponsereclamationRepository->findOneBy(['reclamation' => $reclamation]);
            if ($response) {
                $reponses[$reclamation->getId()] = $response;
            }
    
            // 🔹 Compter les réponses non lues pour chaque réclamation
            $unreadResponsesCounts[$reclamation->getId()] = $reponsereclamationRepository->count([
                'reclamation' => $reclamation,
                'isRead' => false
            ]);
        }
    
        // 🟢 Récupérer le nombre total de réponses non lues
        $unreadResponsesCount = $this->getUnreadResponsesCount($session, $reponsereclamationRepository);
    
        return $this->render('frontOffice/my_reclamations.html.twig', [
            'reclamations' => $reclamations,
            'reponses' => $reponses,
            'userEmail' => $userEmail,
            'unreadResponsesCount' => $unreadResponsesCount, 
            'unreadResponsesCounts' => $unreadResponsesCounts, // ✅ Ajout du tableau des notifications par réclamation
        ]);
    }
    
    

//     #[Route('/my-reclamations', name: 'app_my_reclamations', methods: ['GET'])]
// public function myReclamations(
//     ReclamationRepository $reclamationRepository, 
//     ReponsereclamationRepository $reponsereclamationRepository, 
//     Request $request
// ): Response
// {
//     $session = $request->getSession();
//     $userEmail = $session->get('user_email');

//     if (!$userEmail) {
//         $this->addFlash('error', 'Veuillez vous connecter pour voir vos réclamations.');
//         return $this->redirectToRoute('login');
//     }

//     $reclamations = $reclamationRepository->findBy(['user_email' => $userEmail], ['date_soumission' => 'DESC']);
//     $reponses = [];
    
//     foreach ($reclamations as $reclamation) {
//         $response = $reponsereclamationRepository->findOneBy(['reclamation' => $reclamation]);
//         if ($response) {
//             $reponses[$reclamation->getId()] = $response;
//         }
//     }

//     // 🟢 Récupérer le nombre de réponses non lues
//     $unreadResponsesCount = $this->getUnreadResponsesCount($session, $reponsereclamationRepository);


//     return $this->render('frontOffice/my_reclamations.html.twig', [
//         'reclamations' => $reclamations,
//         'reponses' => $reponses,
//         'userEmail' => $userEmail, 
//         'unreadResponsesCount' => $unreadResponsesCount, // ✅ Ajout de la variable
//     ]);
// }


    #[Route('/reclamations/stats', name: 'app_reclamation_stats', methods: ['GET'])]
    public function reclamationStats(ReclamationRepository $reclamationRepository): Response
    {
        $stats = [
            'En attente' => $reclamationRepository->count(['status' => 'En attente']),
            'En cours' => $reclamationRepository->count(['status' => 'En cours']),
            'Résolue' => $reclamationRepository->count(['status' => 'Résolue']),
        ];
    
        return $this->render('backOffice/reclamation.html.twig', [
            'stats' => $stats,
        ]);
    }
    


}
