<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\MessageRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reponse')]
class ReponseController extends AbstractController
{
    #[Route('/', name: 'app_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepository, MessageRepository $messageRepository): Response
    {
        return $this->render('reponse/index.html.twig', [
            'messages' => $messageRepository->findAll(),
            'reponses' => $reponseRepository->findAll(),
        ]);
    }

    #[Route('/new/{id}', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(int $id, Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $message = $messageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message non trouvé.');
        }

        $reponse = new Reponse();
        $reponse->setMessage($message);

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index');
        }

        return $this->render('reponse/new.html.twig', [
            'message' => $message,
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        // Fetch the associated message for the response
        $message = $reponse->getMessage();
    
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
            'message' => $message,  // Pass the message to the template
        ]);
    }
    

    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_reponse_index');
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_index');
    }
}
