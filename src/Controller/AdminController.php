<?php
namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Entity\Reponsereclamation;
use App\Form\ReponsereclamationType;
use App\Repository\ReponsereclamationRepository;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReponseRepository;
use App\Repository\MessageRepository;
use App\Entity\Message;

class AdminController extends AbstractController
{
    private function getLoggedInUser(Request $request, EntityManagerInterface $entityManager): ?User
    {
        $email = $request->getSession()->get('user_email');
        if (!$email) {
            return null;
        }
        return $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'utilisateur connecté
        $user = $this->getLoggedInUser($request, $entityManager);
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }

        // Si c'est un admin, récupérer la liste des utilisateurs
        $users = [];
        if ($user->getRole() === 'ROLE_ADMIN') {
            $users = $entityManager->getRepository(User::class)->findAll();
        } else {
            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('base_back.html.twig', [
            'user' => $user,
            'users' => $users
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($user = $this->getLoggedInUser($request, $entityManager)) {
            if ($user->getRole() === 'ROLE_ADMIN') {
                return $this->redirectToRoute('admin_dashboard');
            } else {
                return $this->redirectToRoute('app_home');
            }
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');
            
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($user && password_verify($password, $user->getMotDePasse())) {
                $request->getSession()->set('user_email', $user->getEmail());
                if ($user->getRole() === 'ROLE_ADMIN') {
                    return $this->redirectToRoute('admin_dashboard');
                } else {
                    return $this->redirectToRoute('app_home');
                }
            } else {
                $this->addFlash('error', 'Email ou mot de passe incorrect');
            }
        }

        return $this->render('backOffice/auth-normal-sign-in.html.twig');
    }

    #[Route('/sign-in', name: 'app_signin')]
    public function signin(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getLoggedInUser($request, $entityManager)) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = new User();
        $user->setDateInscription(new \DateTime());
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $user->setPhoto($base64Image);
            }

            $user->setMotDePasse(password_hash($user->getMotDePasse(), PASSWORD_DEFAULT));
            
            if (!$user->getRole()) {
                $user->setRole('ROLE_USER');
            }
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('login');
        }

        return $this->render('backOffice/auth-sign-up.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }
        
        return $this->render('backOffice/profile.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }
    
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $user->setPhoto($base64Image);
            }
    
            $newPassword = $form->get('mot_de_passe')->getData();
            if (!empty($newPassword)) {
                $user->setMotDePasse(password_hash($newPassword, PASSWORD_DEFAULT));
            }
    
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès');
            return $this->redirectToRoute('user_profile');
        }
    
        return $this->render('backOffice/profile_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/profile/delete', name: 'user_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $user = $this->getLoggedInUser($request, $entityManager);
            if (!$user) {
                $this->addFlash('error', 'Veuillez vous connecter');
                return $this->redirectToRoute('login');
            }
    
            $token = $request->request->get('_token');
            
            if (!$this->isCsrfTokenValid('delete'.$user->getId(), $token)) {
                $this->addFlash('error', 'Token de sécurité invalide');
                return $this->redirectToRoute('user_profile');
            }
    
            $request->getSession()->clear();
            
            $entityManager->remove($user);
            $entityManager->flush();
    
            $this->addFlash('success', 'Votre compte a été supprimé avec succès');
            return $this->redirectToRoute('login');
    
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('user_profile');
        }
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->remove('user_email');
        $request->getSession()->clear();
        
        $this->addFlash('success', 'Vous avez été déconnecté');
        return $this->redirectToRoute('login');
    }

    #[Route('/user/{id}/modify', name: 'user_modify')]
    public function modifyUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }

        if ($currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $userToModify = $entityManager->getRepository(User::class)->find($id);
        if (!$userToModify) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $form = $this->createForm(UserType::class, $userToModify, [
            'is_edit' => true,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $userToModify->setPhoto($base64Image);
            }

            $newPassword = $form->get('mot_de_passe')->getData();
            if (!empty($newPassword)) {
                $userToModify->setMotDePasse(password_hash($newPassword, PASSWORD_DEFAULT));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('backOffice/user_modify.html.twig', [
            'form' => $form->createView(),
            'user' => $currentUser,
            'userToModify' => $userToModify
        ]);
    }

    #[Route('/user/{id}/delete', name: 'user_delete', methods: ['POST', 'DELETE'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $userToDelete = $entityManager->getRepository(User::class)->find($id);
        if (!$userToDelete) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            if ($userToDelete->getId() === $currentUser->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
                return $this->redirectToRoute('admin_dashboard');
            }

            $entityManager->remove($userToDelete);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/add', name: 'user_add')]
    public function addUser(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $newUser = new User();
        $form = $this->createForm(UserType::class, $newUser, [
            'is_edit' => false
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $newUser->setPhoto($base64Image);
            }

            $newUser->setMotDePasse(password_hash($newUser->getMotDePasse(), PASSWORD_DEFAULT));
            
            $newUser->setDateInscription(new \DateTime());

            $entityManager->persist($newUser);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur ajouté avec succès');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('backOffice/user_add.html.twig', [
            'form' => $form->createView(),
            'user' => $currentUser
        ]);
    }

    #[Route('/conditions-utilisation', name: 'conditions_utilisation')]
public function conditionsUtilisation(): Response
{
    return $this->render('backOffice/condition_utilisation.html.twig');
}


    #[Route('/discussions', name: 'discussions')]
    public function dscussion(ReponseRepository $reponseRepository, MessageRepository $messageRepository): Response
    { $messages = $messageRepository->findAll();
        $reponses = $reponseRepository->findAll(); 
        return $this->render('backOffice/discussions.html.twig',[
            'messages' => $messages,
            'reponses' => $reponses,
        ]);

    }
    #[Route('/discussions/edit', name: 'discussionsedit')]
    public function descussion(ReponseRepository $reponseRepository, MessageRepository $messageRepository): Response
    { $messages = $messageRepository->findAll();
        $reponses = $reponseRepository->findAll(); 
        return $this->render('backOffice/discussionsedit.html.twig',[
            'messages' => $messages,
            'reponses' => $reponses,
        ]);

    }
    #[Route('/{id}', name: 'ddelete', methods: ['POST'])]
public function delete(Request $request, Message $message, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
        $entityManager->remove($message);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
}

   

    ////////////////////////////////////////////////reclamation////////////////////////////////////////////


    // #[Route('/reclamations', name: 'app_reclamation_back')]
    // public function reclamation(ReclamationRepository $reclamationRepository): Response
    // {
    //     $reclamations = $reclamationRepository->findAll();
    //     $stats = [
    //         'En attente' => $reclamationRepository->count(['status' => 'En attente']),
    //         'En cours' => $reclamationRepository->count(['status' => 'En cours']),
    //         'Résolue' => $reclamationRepository->count(['status' => 'Résolue']),
    //     ];

     

    //     return $this->render('backOffice/reclamation.html.twig', [
    //         'reclamations' => $reclamations,
    //         'stats' => $stats,
    //     ]);
    // }

    #[Route('/reclamations', name: 'app_reclamation_back')]
        public function reclamation(ReclamationRepository $reclamationRepository, ReponsereclamationRepository $reponsereclamationRepository, Request $request
        ): Response {
            // 🔹 Récupération de l'email utilisateur depuis la session
            $session = $request->getSession();
            $userEmail = $session->get('user_email');

            // 🔹 Vérification de l'utilisateur connecté
            if (!$userEmail) {
                $this->addFlash('error', 'Veuillez vous connecter.');
                return $this->redirectToRoute('login');
            }

            // 🔹 Récupération des réclamations
            $reclamations = $reclamationRepository->findAll();

            // 🔹 Statistiques des statuts
            $stats = [
                'En attente' => $reclamationRepository->count(['status' => 'En attente']),
                'En cours' => $reclamationRepository->count(['status' => 'En cours']),
                'Résolue' => $reclamationRepository->count(['status' => 'Résolue']),
            ];

            // 🔹 Compter les nouvelles réponses non lues
            $unreadResponsesCount = $reponsereclamationRepository->countUnreadResponsesForUser($userEmail);

            return $this->render('backOffice/reclamation.html.twig', [
                'reclamations' => $reclamations,
                'stats' => $stats,
                'unreadResponsesCount' => $unreadResponsesCount, // 🔹 Ajout du compteur pour les notifications
            ]);
        }


     
    #[Route('/{id}/delete', name: 'app_reclamation_delete_back', methods: ['POST'])]
    public function deleterec(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès !');
    
            // Redirect to the back office route (update with the correct route name)
            return $this->redirectToRoute('app_reclamation_back');  
        }
    
        $this->addFlash('error', 'Échec de la suppression.');
        return $this->redirectToRoute('app_reclamation_back'); // Ensure fallback redirection
    }
    
} 