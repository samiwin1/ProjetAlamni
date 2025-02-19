<?php

namespace App\Controller;

use App\Entity\Reponsereclamation;
use App\Form\ReponsereclamationType;
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
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('frontOffice/reclamation.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
        ]);
    }


   
    //  #[Route('/reclamation/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]

    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $reclamation = new Reclamation();
    //     $reclamation->setStatus('En attente'); // ✅ Définit "En attente" par défaut
    
    //     $form = $this->createForm(ReclamationType::class, $reclamation);
    //     $form->handleRequest($request); 
    
    //     if ($form->isSubmitted()) {
    //       // **Validate User Email**
    //         if (!$reclamation->getUserEmail()) {
    //             $errors[] = "L'email utilisateur est obligatoire.";
    //         } elseif (!filter_var($reclamation->getUserEmail(), FILTER_VALIDATE_EMAIL)) {
    //             $errors[] = "L'email utilisateur n'est pas valide.";
    //         }
    
    //         // **Validate Admin Email**
    //         if (!$reclamation->getAdminMail()) {
    //             $errors[] = "Le destinataire est obligatoire.";
    //         } elseif (!filter_var($reclamation->getAdminMail(), FILTER_VALIDATE_EMAIL)) {
    //             $errors[] = "L'email administrateur n'est pas valide.";
    //         }
    
    //         // **Validate Role**
    //         if (!$reclamation->getRole()) {
    //             $errors[] = "Veuillez sélectionner un rôle.";
    //         }
    
    //         // **Validate Subject (Objet)**
    //         if (!$reclamation->getObjet()) {
    //             $errors[] = "L'objet de la réclamation est obligatoire.";
    //         } elseif (strlen($reclamation->getObjet()) < 5) {
    //             $errors[] = "L'objet doit contenir au moins 5 caractères.";
    //         }
    
    //         // **Validate Description**
    //         if (!$reclamation->getDescription()) {
    //             $errors[] = "Veuillez entrer une description.";
    //         } elseif (strlen($reclamation->getDescription()) < 10) {
    //             $errors[] = "La description doit contenir au moins 10 caractères.";
    //         }
    
    //         // **Show Errors or Save Data**
    //         if (!empty($errors)) {
    //             foreach ($errors as $error) {
    //                 $this->addFlash('error', $error);
    //             }
    
    //        } else {
    //             // Save if all validations pass
    //             $entityManager->persist($reclamation);
    //             $entityManager->flush();
    
    //             $this->addFlash('success', 'Votre réclamation a été envoyée avec succès.');
    //             return $this->redirectToRoute('app_reclamation');
    //         }
    //     }
    
    //     return $this->render('frontOffice/reclamation.html.twig', [
    //         'reclamation' => $reclamation,
    //         'form' => $form->createView(),
    //         // 'user' => $this->getUser(), // Passer l'utilisateur connecté au template
    //     ]);
    // }


    #[Route('/reclamation/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
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

    return $this->render('frontOffice/reclamation.html.twig', [
        'form' => $form->createView(),
        'user' => $user
    ]);
}


    // #[Route('/reclamation/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     // 🔹 Récupérer l'utilisateur connecté via la session Symfony
    //     $user = $this->getUser();
    
    //     // 🔹 Vérifier si l'utilisateur est bien connecté
    //     if (!$user instanceof User) {
    //         $this->addFlash('error', 'Veuillez vous connecter pour envoyer une réclamation.');
    //         return $this->redirectToRoute('login');
    //     }
    
    //     // 🔍 Debugging pour voir les valeurs de l'utilisateur connecté
    //     dump($user); 
    
    //     // 🔹 Création d'une nouvelle réclamation avec des valeurs pré-remplies
    //     $reclamation = new Reclamation();
    //     $reclamation->setUser($user); // Associe l'utilisateur connecté
    //     $reclamation->setUserEmail($user->getEmail()); // Remplit automatiquement l'email
    //     $reclamation->setRole($user->getRole()); // Remplit automatiquement le rôle
    //     $reclamation->setStatus('En attente');
    
    //     // 🔹 Création du formulaire avec des champs désactivés (Lecture seule)
    //     $form = $this->createForm(ReclamationType::class, $reclamation, [
    //         'attr' => ['novalidate' => 'novalidate']
    //     ]);
    
    //     dump($form); // 🔍 Vérifie si le formulaire est bien généré
    
    //     $form->handleRequest($request);
    
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->persist($reclamation);
    //         $entityManager->flush();
    
    //         $this->addFlash('success', 'Votre réclamation a été envoyée avec succès.');
    //         return $this->redirectToRoute('app_reclamation_new');
    //     }
    
    //     return $this->render('frontOffice/reclamation.html.twig', [
    //         'form' => $form->createView(),
    //         'user' => $user // ✅ Passer l'utilisateur au template
    //     ]);
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
    public function myReclamations(ReclamationRepository $reclamationRepository, ReponsereclamationRepository $reponsereclamationRepository, Request $request): Response
    {
        // 🔹 Get user email from session
        $session = $request->getSession();
        $userEmail = $session->get('user_email');
    
        // 🔹 Check if the user is logged in
        if (!$userEmail) {
            $this->addFlash('error', 'Veuillez vous connecter pour voir vos réclamations.');
            return $this->redirectToRoute('login');
        }
    
        // 🔎 Fetch all complaints associated with this user
        $reclamations = $reclamationRepository->findBy(['user_email' => $userEmail], ['date_soumission' => 'DESC']);
    
        // 🔎 Fetch responses for each complaint
        $reponses = [];
        foreach ($reclamations as $reclamation) {
            $response = $reponsereclamationRepository->findOneBy(['reclamation' => $reclamation]);
            if ($response) {
                $reponses[$reclamation->getId()] = $response;
            }
        }
    
        return $this->render('frontOffice/my_reclamations.html.twig', [
            'reclamations' => $reclamations,
            'reponses' => $reponses,
            'userEmail' => $userEmail, 
        ]);
    }
    


// #[Route('/my-reclamations', name: 'app_my_reclamations', methods: ['GET'])]
// public function myReclamations(ReclamationRepository $reclamationRepository, Request $request): Response
// {
//     // 🔹 Récupérer l'email de l'utilisateur depuis la session
//     $session = $request->getSession();
//     $userEmail = $session->get('user_email');

//     // 🔹 Vérifier si un utilisateur est connecté
//     if (!$userEmail) {
//         $this->addFlash('error', 'Veuillez vous connecter pour voir vos réclamations.');
//         return $this->redirectToRoute('login');
//     }

//     // 🔎 Récupérer **toutes** les réclamations associées à cet email
//     $reclamations = $reclamationRepository->findBy(['user_email' => $userEmail], ['date_soumission' => 'DESC']); // Tri par date décroissante

//     // 🔍 Vérification dans la console Symfony (décommenter en cas de doute)
//     // dump($reclamations); die();

//     return $this->render('frontOffice/my_reclamations.html.twig', [
//         'reclamations' => $reclamations,
//         'userEmail' => $userEmail, // Pour affichage dans la vue
//     ]);
// }



}
