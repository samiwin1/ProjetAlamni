<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{

    
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('base_back.html.twig', [
            'user' => $user,
            'users' => $users
        ]);
    }

    #[Route('/sign-in', name: 'app_signin')]
    public function signin(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = new User();
        $user->setDateInscription(new \DateTime());
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $imageContent = file_get_contents($photoFile->getPathname());
                    $base64Image = base64_encode($imageContent);
                    $user->setPhoto($base64Image);
                }

                $user->setPassword(password_hash($user->getPassword(), PASSWORD_DEFAULT));
                
                if (!$user->getRoles()) {
                    $user->setRoles(['ROLE_USER']);
                }
                
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Inscription réussie! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
            }
        }

        return $this->render('backOffice/auth-sign-up.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('backOffice/profile.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('app_login');
        }
    
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $imageContent = file_get_contents($photoFile->getPathname());
                    $base64Image = base64_encode($imageContent);
                    $user->setPhoto($base64Image);
                }
        
                $newPassword = $form->get('password')->getData();
                if (!empty($newPassword)) {
                    $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
                }
        
                $entityManager->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès');
                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil');
            }
        }
    
        return $this->render('backOffice/profile_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
    #[Route('/user/{id}/view', name: 'user_view')]
    public function viewUser(User $user): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }
    
        return $this->render('backOffice/user_view.html.twig', [
            'user' => $currentUser,
            'viewUser' => $user
        ]);
    }
    #[Route('/user/{id}/modify', name: 'user_modify')]
    public function modifyUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
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
            try {
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $imageContent = file_get_contents($photoFile->getPathname());
                    $base64Image = base64_encode($imageContent);
                    $userToModify->setPhoto($base64Image);
                }

                $newPassword = $form->get('password')->getData();
                if (!empty($newPassword)) {
                    $userToModify->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
                }

                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès');
                
                return $this->render('base_back.html.twig', [
                    'user' => $currentUser,
                    'users' => $entityManager->getRepository(User::class)->findAll()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification');
            }
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
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
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
            
            return $this->render('base_back.html.twig', [
                'user' => $currentUser,
                'users' => $entityManager->getRepository(User::class)->findAll()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression');
            return $this->redirectToRoute('admin_dashboard');
        }
    }

    #[Route('/user/add', name: 'user_add')]
    public function addUser(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $newUser = new User();
        $form = $this->createForm(UserType::class, $newUser, [
            'is_edit' => false
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $imageContent = file_get_contents($photoFile->getPathname());
                    $base64Image = base64_encode($imageContent);
                    $newUser->setPhoto($base64Image);
                }

                $newUser->setPassword(password_hash($newUser->getPassword(), PASSWORD_DEFAULT));
                $newUser->setDateInscription(new \DateTime());

                $entityManager->persist($newUser);
                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur ajouté avec succès');
                
                return $this->render('base_back.html.twig', [
                    'user' => $currentUser,
                    'users' => $entityManager->getRepository(User::class)->findAll()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout');
            }
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



    #[Route('/conversations', name: 'conversations_show')]
    public function conversationsShow(): Response
    {
        return $this->render('coversation_back/index.html.twig');
    }
 



}
