<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/planning-management')]
final class PlanningController extends AbstractController {
    
    // Display all planning records
    #[Route('/', name: 'app_planning_index', methods: ['GET'])]
    public function index(PlanningRepository $planningRepository): Response
    {
        $plannings = $planningRepository->findAll();
        $user = $this->getUser(); // Access the logged-in user

        return $this->render('planning/index.html.twig', [
            'plannings' => $plannings,
            'user' => $user, // Pass user to the view
        ]);
    }

    // src/Controller/PlanningController.php



    #[Route('/planning/pdf', name: 'planning_pdf')]
    public function generatePdf(PlanningRepository $planningRepository): Response
    {
        // Fetch all planning data
        $plannings = $planningRepository->findAll();
    
        // Render the planning data into an HTML template
        $html = $this->renderView('planning/pdf/planning.html.twig', [
            'plannings' => $plannings,
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
    // Create a new planning record
    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Automatically associate the planning with the logged-in user
            $planning->setUser($this->getUser());
    
            $entityManager->persist($planning);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('planning/new.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }

    // Show a specific planning record
    #[Route('/{id}', name: 'app_planning_show', methods: ['GET'])]
    public function show(Planning $planning): Response
    {
        return $this->render('planning/show.html.twig', [
            'planning' => $planning,
            'user' => $this->getUser(), // Pass user to the view
        ]);
    }

    // Edit an existing planning record
    #[Route('/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Planning $planning, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('planning/edit.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
            'user' => $this->getUser(), // Pass user to the view
        ]);
    }

    // Delete a planning record
    #[Route('/{id}/delete', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planning->getId(), $request->request->get('_token'))) {
            $entityManager->remove($planning);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
    }
}