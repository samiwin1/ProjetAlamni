<?php

namespace App\Controller;

use App\Entity\Devoir;
use App\Entity\Cours;
use App\Form\DevoirType;
use App\Repository\DevoirRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/devoir')]
class DevoirController extends AbstractController
{
    #[Route('/', name: 'app_devoir_index', methods: ['GET'])]
    public function index(DevoirRepository $devoirRepository): Response
    {
        return $this->render('devoir/index.html.twig', [
            'devoirs' => $devoirRepository->findAll(),
        ]);
    }

    #[Route('/new/{coursId}', name: 'app_devoir_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ?int $coursId = null): Response
    {
        $devoir = new Devoir();
        
        if ($coursId) {
            $cours = $entityManager->getRepository(Cours::class)->find($coursId);
            if ($cours) {
                $devoir->setCours($cours);
            }
        }
        
        $form = $this->createForm(DevoirType::class, $devoir);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supportFile = $form->get('supportD')->getData();

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

                $devoir->setSupportD($newFilename);
            }

            $entityManager->persist($devoir);
            $entityManager->flush();

            $this->addFlash('success', 'Le devoir a été créé avec succès.');
            return $this->redirectToRoute('app_devoir_by_cours', ['id' => $devoir->getCours()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('devoir/new.html.twig', [
            'devoir' => $devoir,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_devoir_show', methods: ['GET'])]
    public function show(Devoir $devoir): Response
    {
        return $this->render('client/show.html.twig', [
            'devoir' => $devoir,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_devoir_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Devoir $devoir, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(DevoirType::class, $devoir);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supportFile = $form->get('supportD')->getData();

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

                $devoir->setSupportD($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le devoir a été modifié avec succès.');
            return $this->redirectToRoute('app_devoir_by_cours', ['id' => $devoir->getCours()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('devoir/edit.html.twig', [
            'devoir' => $devoir,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_devoir_delete', methods: ['POST'])]
    public function delete(Request $request, Devoir $devoir, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$devoir->getId(), $request->request->get('_token'))) {
            $coursId = $devoir->getCours()->getId();
            try {
                $entityManager->remove($devoir);
                $entityManager->flush();
                $this->addFlash('success', 'Le devoir a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du devoir.');
            }
            return $this->redirectToRoute('app_devoir_by_cours', ['id' => $coursId], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_devoir_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/cours/{id}', name: 'app_devoir_by_cours', methods: ['GET'])]
    public function listByCours(Cours $cours, DevoirRepository $devoirRepository): Response
    {
        $devoirs = $devoirRepository->findBy(['cours' => $cours]);
        
        return $this->render('devoir/by_cours.html.twig', [
            'cours' => $cours,
            'devoirs' => $devoirs,
        ]);
    }
}