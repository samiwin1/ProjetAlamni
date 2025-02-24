<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    #[Route(name: 'app_cours_index', methods: ['GET'])]
    public function index(CoursRepository $coursRepository): Response
    {
        return $this->render('cours/index.html.twig', [
            'cours' => $coursRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle the image upload if a file was uploaded
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'), // Folder path from config/services.yaml
                        $newFilename
                    );

                    // Save the file name in the database
                    $cour->setImage($newFilename);
                } catch (FileException $e) {
                    // Handle any errors during the file upload
                    $this->addFlash('error', 'Failed to upload the image.');
                    return $this->render('cours/new.html.twig', [
                        'cour' => $cour,
                        'form' => $form,
                    ]);
                }
            }

            // Persist the Cours entity (with the image filename)
            $entityManager->persist($cour);
            $entityManager->flush();

            $this->addFlash('success', 'Le cours a été créé avec succès.');
            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('cours/show.html.twig', [
            'cour' => $cour,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload during edit if a new file is provided
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    // Update the image path in the database
                    $cour->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload the image.');
                    return $this->render('cours/edit.html.twig', [
                        'cour' => $cour,
                        'form' => $form,
                    ]);
                }
            }

            // Update other course details
            $entityManager->flush();

            $this->addFlash('success', 'Le cours a été modifié avec succès.');
            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/edit.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($cour);
                $entityManager->flush();
                $this->addFlash('success', 'Le cours a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du cours.');
            }
        }

        return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/cours/search', name: 'app_cours_search_by_matiere')]
public function rechercheParMatiere(Request $request, EntityManagerInterface $entityManager): Response
{
    $keyword = $request->query->get('keyword');

    if ($keyword) {
        // Recherche par description (LIKE pour une recherche partielle)
        $events = $entityManager
            ->getRepository(Cours::class)
            ->createQueryBuilder('e')
            ->where('e.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Récupérer tous les événements
        $events = $entityManager
            ->getRepository(Cours::class)
            ->findAll();
    }

    return $this->render('cours/index.html.twig', [
        'events' => $events,
        'searchKeyword' => $keyword // Ajout du mot-clé dans le template
    ]);
}
}
