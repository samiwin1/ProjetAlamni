<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Repository\ReponsereclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\User;
use App\Repository\PlanningRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\UserType;

class FrontController extends AbstractController
{
    private function getLoggedInUser(Request $request, EntityManagerInterface $entityManager): ?User
    {
        $email = $request->getSession()->get('user_email');
        if (!$email) {
            return null;
        }
        return $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function getUnreadResponsesCount(SessionInterface $session, ReponsereclamationRepository $reponsereclamationRepository): int
    {
        $userEmail = $session->get('user_email');
        if (!$userEmail) {
            return 0; // Aucun utilisateur connecté
        }
        
        return $reponsereclamationRepository->countUnreadResponsesForUser($userEmail);
    }

    #[Route('/', name: 'app_home')]
    public function home(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, ReponsereclamationRepository $reponsereclamationRepository, ): Response
    {

        $unreadResponsesCount = $this->getUnreadResponsesCount($session, $reponsereclamationRepository);

        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('base_front.html.twig', [
            'user' => $user,
            'unreadResponsesCount' => $unreadResponsesCount,
        ]); 


    }

    #[Route('/about', name: 'app_about')]
    public function about(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/about.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/reclamation', name: 'app_reclamation')]
    public function reclamation(): Response
    {
        return $this->render('frontOffice/reclamation.html.twig');
    }

    #[Route('/front/reclamation/{id}', name: 'app_reclamation_front_show', methods: ['GET'])]
    public function showFront(Reclamation $reclamation): Response
    {
          return $this->render('reclamation/show.html.twig', [
        'reclamation' => $reclamation,
    ]);
}

    #[Route('/classes', name: 'app_classes')]
    public function classes(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/classes.html.twig', [
            'user' => $user
        ]); 
    }
    #[Route('/facility', name: 'app_facility')]
    public function facility(Request $request, EntityManagerInterface $entityManager, PlanningRepository $planningRepository): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
    
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter');
            return $this->redirectToRoute('login');
        }
    
        $planning = [];
    
        // Check if the user is a student
        if (in_array('ROLE_ELEVE', $user->getRoles())) {
            $planning = $planningRepository->findPlanningForUserLevel($user->getNiveau());
        }
        // Check if the user is a teacher
        elseif (in_array('ROLE_ENSEIGNANT', $user->getRoles())) {
            $teacherName = $user->getNom() . ' ' . $user->getPrenom();
            $planning = $planningRepository->findPlanningForTeacher($teacherName);
        }
    
        return $this->render('frontOffice/facility.html.twig', [
            'user' => $user,
            'planning' => $planning
        ]);
    
    }

    #[Route('/team', name: 'app_team')]
    public function team(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/team.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/call-to-action', name: 'app_cta')]
    public function callToAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/call-to-action.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/appointment', name: 'app_appointment')]
    public function appointment(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/appointment.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/contact.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/404', name: 'app_404')]
    public function notFound(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getLoggedInUser($request, $entityManager);
        return $this->render('frontOffice/404.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile-utilisateur', name: 'profile_utilisateur')]
public function profileUtilisateur(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getLoggedInUser($request, $entityManager);
    if (!$user) {
        $this->addFlash('error', 'Veuillez vous connecter');
        return $this->redirectToRoute('login');
    }
    
    return $this->render('frontOffice/profileUtilisateur.html.twig', [
        'user' => $user
    ]);
}

#[Route('/profile-utilisateur/edit', name: 'front_profile_edit')]
public function editProfileFront(Request $request, EntityManagerInterface $entityManager): Response
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
        return $this->redirectToRoute('profile_utilisateur');
    }

    return $this->render('frontOffice/editProfile.html.twig', [
        'form' => $form->createView(),
        'user' => $user
    ]);
}
}