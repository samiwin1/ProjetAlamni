<?php
namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\DevoirRepository;

use App\Entity\Rating;
use App\Entity\Cours;
use App\Form\RatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Cours $cours, EntityManagerInterface $entityManager): Response
    {
        $rating = new Rating();
        $rating->setCours($cours);
        $rating->setUser($this->getUser());

        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rating);
            $entityManager->flush();

            $this->addFlash('success', 'Votre évaluation a été enregistrée avec succès !');
            return $this->redirectToRoute('app_course_show', ['id' => $cours->getId()]);
        }

        return $this->render('rating/new.html.twig', [
            'rating' => $rating,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rating_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
{
    // Vérifier si l'utilisateur actuel est l'auteur de l'évaluation
    if ($rating->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette évaluation.');
    }

    $form = $this->createForm(RatingType::class, $rating);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', 'Votre évaluation a été mise à jour avec succès !');
        return $this->redirectToRoute('app_course_show', ['id' => $rating->getCours()->getId()]);
    }

    return $this->render('rating/edit.html.twig', [
        'rating' => $rating,
        'form' => $form->createView(),
        'cours' => $rating->getCours()
    ]);
}
#[Route('/course/{id}', name: 'app_course_show')]
public function show(
    Cours $cour, 
    DevoirRepository $devoirRepository, 
    CoursRepository $coursRepository,
    Request $request, 
    EntityManagerInterface $entityManager
): Response {
    // Get the assignments related to the course
    $devoirs = $devoirRepository->findBy(['cours' => $cour]);

    // Get related courses
    $relatedCourses = $coursRepository->findBy(
        ['matiereC' => $cour->getMatiereC()],
        null,
        3
    );

    // Create new rating form only if user is logged in
    $rating_form = null;
    $user = $this->getUser();
    if ($user) {
        $rating = new Rating();
        $rating->setCours($cour);
        $rating->setUser($user);
        
        $rating_form = $this->createForm(RatingType::class, $rating, [
            'action' => $this->generateUrl('app_rating_new', ['id' => $cour->getId()]),
            'method' => 'POST',
        ]);
    }

    return $this->render('client/showcourse.html.twig', [
        'cour' => $cour,
        'devoirs' => $devoirs,
        'relatedCourses' => $relatedCourses,
        'rating_form' => $rating_form ? $rating_form->createView() : null,
    ]);
}      
    
}