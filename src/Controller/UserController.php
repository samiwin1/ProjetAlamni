<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    private function getLoggedInUser(Request $request, EntityManagerInterface $entityManager): ?User
    {
        $email = $request->getSession()->get('user_email');
        if (!$email) {
            return null;
        }
        return $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }

        if ($user->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'user' => $user
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = new User();
        $user->setDateInscription(new \DateTime());
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $imageContent = file_get_contents($photoFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $user->setPhoto($base64Image);
            }

            $user->setMotDePasse(password_hash($user->getMotDePasse(), PASSWORD_DEFAULT));
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'logged_user' => $currentUser
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'logged_user' => $currentUser
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                $user->setMotDePasse(password_hash($newPassword, PASSWORD_DEFAULT));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit_form.html.twig', [
            'user' => $user,
            'form' => $form,
            'logged_user' => $currentUser
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getLoggedInUser($request, $entityManager);
        if (!$currentUser || $currentUser->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Empêcher la suppression de son propre compte via cette route
            if ($user->getId() === $currentUser->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte ici');
                return $this->redirectToRoute('app_user_index');
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
