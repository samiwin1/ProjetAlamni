<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Favorite;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $searchTerm = $request->query->get('search', '');
        $events = $eventRepository->findBySearchTerm($searchTerm);

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('images_directory');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (!is_writable($uploadDir)) {
                    $this->addFlash('error', 'Upload directory is not accessible.');
                    return $this->redirectToRoute('app_event_new');
                }

                try {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    $imageFile->move($uploadDir, $newFilename);
                    $event->setImage('uploads/images/' . $newFilename); // Change backslashes to slashes
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the image.');
                    return $this->redirectToRoute('app_event_new');
                }
            }

            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $oldImage = $event->getImage();
        
            if ($imageFile) {
                $uploadDir = $this->getParameter('images_directory');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
        
                if (!is_writable($uploadDir)) {
                    $this->addFlash('error', 'Upload directory is not accessible.');
                    return $this->redirectToRoute('app_event_edit', ['id' => $event->getId()]);
                }
        
                try {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($uploadDir, $newFilename);
                    $event->setImage('uploads/images/' . $newFilename); // Ensure the path is correct
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the image.');
                    return $this->redirectToRoute('app_event_edit', ['id' => $event->getId()]);
                }
            } else {
                $event->setImage($oldImage);
            }
        
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Event updated successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating the event.');
                return $this->redirectToRoute('app_event_edit', ['id' => $event->getId()]);
            }
        
            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_delete', methods: ['GET' , 'DELETE'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event deleted successfully');
        }

        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}/favorite', name: 'event_favorite', methods: ['POST'])]
    public function favorite(Event $event, EntityManagerInterface $entityManager, FavoriteRepository $favoriteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $favorite = $favoriteRepository->findByUserAndEvent($user, $event);
        if ($favorite) {
            $entityManager->remove($favorite);
            $entityManager->flush();
            return $this->json(['message' => 'Event removed from favorites']);
        } else {
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setEvent($event);
            $entityManager->persist($favorite);
            $entityManager->flush();
            return $this->json(['message' => 'Event added to favorites']);
        }
    }

    #[Route('/{id}/details', name: 'event_details', methods: ['GET'])]
    public function details(Event $event): Response
    {
        return $this->render('client/details.html.twig', [
            'event' => $event,
        ]);
    }
}