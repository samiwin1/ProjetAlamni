<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Repository\PlanningRepository;
use App\Form\UserType;
use App\Entity\Cours;
use App\Entity\Devoir;
use App\Repository\CoursRepository;
use App\Repository\DevoirRepository;

class FrontController extends AbstractController
{
    private function getLoggedInUser(Request $request, EntityManagerInterface $entityManager): ?User
    {
        if (!$this->getUser()) {
            return null;
        }
        return $this->getUser();
    }

    #[Route('/', name: 'app_home')]
    public function home(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('base_front.html.twig', [
            'user' => $this->getUser()
        ]); 
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('frontOffice/about.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/classes', name: 'app_classes')]
    public function classes(): Response
    {
        return $this->render('frontOffice/classes.html.twig', [
            'user' => $this->getUser()
        ]); 
    }

    #[Route('/facility', name: 'app_facility')]
    public function facility(PlanningRepository $planningRepository): Response
    {
        return $this->render('frontOffice/facility.html.twig', [
            'user' => $this->getUser(),
            'planning' => $planningRepository->findAll()
        ]);
    }

    #[Route('/team', name: 'app_team')]
    public function team(): Response
    {
        return $this->render('frontOffice/team.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('frontOffice/contact.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    // Routes protégées
    #[Route('/profile-utilisateur', name: 'profile_utilisateur')]
    public function profileUtilisateur(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        return $this->render('frontOffice/profileUtilisateur.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/profile-utilisateur/edit', name: 'front_profile_edit')]
    public function editProfileFront(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
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

    #[Route('/appointment', name: 'app_appointment')]
    public function appointment(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        return $this->render('frontOffice/appointment.html.twig', [
            'user' => $this->getUser()
        ]);
    }
    #[Route('/courses', name: 'app_courses')]
    public function courses(CoursRepository $coursRepository): Response
    {
        // Retrieve all courses from the database
        $courses = $coursRepository->findAll();

        // Pass the courses data to the template
        return $this->render('client/courses.html.twig', [
            'courses' => $courses,
        ]);
    }
}