<?php

namespace App\Controller;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Ratting;
use App\Entity\Reservation;
use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use App\Repository\RattingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/affichage')]
final class affichageController extends AbstractController
{
    #[Route(name: 'app_affichage')]
    public function index(): Response
    {
        return $this->render('affichage/index.html.twig');
    }

    #[Route('/event', name: 'app_event')]
    public function events(EventRepository $eventRepository): Response
    {
        // Retrieve all events from the database
        $events = $eventRepository->findAll();

        // Ensure $events is not null
        if ($events === null) {
            throw $this->createNotFoundException('No events found');
        }

        // Pass the events data to the template
        return $this->render('affichage/event.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/{id}', name: 'app_event1_show')]
    public function show(Event $event, EventRepository $eventRepository): Response
    {
        // Get related events (e.g., same category)
        $relatedevents = $eventRepository->findBy(
            ['category' => $event->getCategory()],
            null,
            3
        );

        // Ensure $relatedevents is not null
        if ($relatedevents === null) {
            throw $this->createNotFoundException('No related events found');
        }

        // Get rattings for the event
        $rattings = $event->getRattings();

        return $this->render('affichage/details.html.twig', [
            'event' => $event,
            'relatedevents' => $relatedevents,
            'rattings' => $rattings,
        ]);
    }

    

    #[Route('/category/{id}', name: 'app_category_show')]
    public function showCategorie(Category $category, EventRepository $eventRepository): Response
    {
        // Find events by category
        $events = $eventRepository->findBy(['category' => $category]);

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'events' => $events,
        ]);
    }

    #[Route('/favorites', name: 'app_favorites')]
    public function favorites(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        $favorites = $user->getFavoriteEvents();

        return $this->render('affichage/favorites.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    #[Route('/event/{id}/rate', name: 'rate_event', methods: ['POST'])]
    public function rateEvent($id, Request $request, EventRepository $eventRepository, RattingRepository $rattingRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to rate an event.');
            return $this->redirectToRoute('app_event1_show', ['id' => $id]);
        }

        $rattingValue = $request->request->get('ratting');

        // Find the event by ID
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Check if the user has already rated this event
        $existingRatting = $rattingRepository->findOneBy(['event' => $event, 'user' => $user]);
        if ($existingRatting) {
            $this->addFlash('error', 'You have already rated this event.');
            return $this->redirectToRoute('app_event1_show', ['id' => $id]);
        }

        // Create a new ratting
        $ratting = new Ratting();
        $ratting->setEvent($event);
        $ratting->setUser($user);
        $ratting->setRatting($rattingValue);

        // Save the ratting
        $entityManager->persist($ratting);
        $entityManager->flush();

        return $this->redirectToRoute('app_event1_show', ['id' => $id]);
    }
}
