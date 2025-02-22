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

use Dompdf\Dompdf;
use Dompdf\Options;

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
            return $this->render('backOffice/user_modify.html.twig', [
                'form' => $form->createView(),
                'user' => $currentUser,
                'userToModify' => $userToModify,
                'message' => 'Utilisateur modifié avec succès',
            ]);
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
            // Gestion de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $user->setPhoto($base64Image);
            }

            // Gestion du mot de passe
            $newPassword = $form->get('mot_de_passe')->getData();
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $user->setPassword($hashedPassword);
            }

            // Mise à jour des autres champs
            $user->setNom($form->get('nom')->getData());
            $user->setPrenom($form->get('prenom')->getData());
            $user->setEmail($form->get('email')->getData());

            // Sauvegarde des modifications
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
    // Vérification des droits d'accès
    $currentUser = $this->getUser();
    if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
        $this->addFlash('error', 'Accès non autorisé');
        return $this->redirectToRoute('admin_dashboard');
    }

    // Récupération de l'utilisateur à modifier
    $userToModify = $entityManager->getRepository(User::class)->find($id);
    if (!$userToModify) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    // Création du formulaire
    $form = $this->createForm(UserType::class, $userToModify, [
        'is_edit' => true,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Gestion de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $userToModify->setPhoto($base64Image);
            }

            // Gestion du mot de passe
            $newPassword = $form->get('mot_de_passe')->getData(); // Changé de 'password' à 'mot_de_passe'
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $userToModify->setPassword($hashedPassword);
            }

            // Mise à jour des autres champs
            $userToModify->setNom($form->get('nom')->getData());
            $userToModify->setPrenom($form->get('prenom')->getData());
            $userToModify->setEmail($form->get('email')->getData());

            // Sauvegarde des modifications
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès');

            // Redirection vers le dashboard
            return $this->redirectToRoute('admin_dashboard');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification: ' . $e->getMessage());
        }
    }

    // Affichage du formulaire
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
        $this->addFlash('error', 'Utilisateur non trouvé');
        return $this->redirectToRoute('admin_dashboard');
    }

    try {
        if ($userToDelete->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_dashboard');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $entityManager->remove($userToDelete);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès');
        
        // Redirection directe vers le dashboard
        return $this->redirectToRoute('admin_dashboard');

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


}
