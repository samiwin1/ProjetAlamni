<?php
namespace App\Controller;


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
    
}