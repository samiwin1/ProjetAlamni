<?php

namespace App\Controller;
use App\Entity\Category;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client')]
final class ClientController extends AbstractController
{
    #[Route(name: 'app_client')]
    public function index(): Response
    {
        return $this->render('client/index.html.twig');
    }

    #[Route('/event', name: 'app_event')]
    public function events(eventRepository $eventRepository): Response
    {
        // Retrieve all events from the database
        $events = $eventRepository->findAll();

        // Pass the events data to the template
        return $this->render('client/event.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/{id}', name: 'app_event1_show')]
    public function show(event $event, CategoryRepository $CategoryRepository, EventRepository $eventRepository): Response
    {
        // Get the assignments related to the course
        $categorie = $CategoryRepository->findBy(['events' => $event]);

        // Get related events (e.g., same subject)
        $relatedevents = $eventRepository->findBy(
            ['nom' => $event->getNom()],
            null,
            3
        );

        return $this->render('client/eventShow.html.twig', [
            'event' => $event,
            'categorie' => $categorie,
            'relatedevents' => $relatedevents,
        ]);
    }

    #[Route('/category/{id}', name: 'app_category_show')]
    public function showCategorie(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }
  
    
}
