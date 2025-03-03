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
use Knp\Snappy\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/courses', name: 'app_courses')]
    public function courses(CoursRepository $coursRepository): Response
    {
        $user = $this->getUser();
        $courses = $coursRepository->findAll(); // Récupère tous les cours
        return $this->render('client/courses.html.twig', [
            'courses' => $courses,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('images_directory'), $newFilename);
                    $cour->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->render('cours/new.html.twig', [
                        'cour' => $cour,
                        'form' => $form,
                    ]);
                }
            }
            $supportFile = $form->get('supportC')->getData();
            if ($supportFile) {
                $originalFilename = pathinfo($supportFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$supportFile->guessExtension();

                try {
                    $supportFile->move(
                        $this->getParameter('support_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

                $cour->setSupportC($newFilename);
            }

            // Sauvegarde de l'entité Cours
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
            // Gestion de l'image pendant la modification
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('images_directory'), $newFilename);
                    $cour->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->render('cours/edit.html.twig', [
                        'cour' => $cour,
                        'form' => $form,
                    ]);
                }
            }

            // Sauvegarde des modifications
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
        if ($this->isCsrfTokenValid('delete' . $cour->getId(), $request->request->get('_token'))) {
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

    // Export PDF
    #[Route('/planning/pdf', name: 'cours_pdf')]
    public function generatePdf(CoursRepository $CoursRepository): Response
    {
        // Fetch all planning data
        $cours = $CoursRepository->findAll();
    
        // Render the planning data into an HTML template
        $html = $this->renderView('cours/pdf/cours.html.twig', [
            'cours' => $cours,
        ]);
    
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
    
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        // Stream the PDF to the browser
        $output = $dompdf->output();
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="planning.pdf"');
    
        return $response;
    }
}
