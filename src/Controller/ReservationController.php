<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/front/event/{id}/reserve', name: 'reserve_ticket', methods: ['GET','POST'])]
    public function reserveTicket($id, Request $request, EventRepository $eventRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $numberOfTickets = $request->request->get('numberOfTickets');

        // Validate numberOfTickets
        if (!is_numeric($numberOfTickets) || $numberOfTickets <= 0) {
            $this->addFlash('error', 'Le nombre de billets doit être un entier positif.');
            return $this->redirectToRoute('app_event1_show', ['id' => $id]);
        }

        $numberOfTickets = (int) $numberOfTickets;

        // Find the event by ID
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Check if the event is full
        $totalReservations = array_reduce($event->getReservations()->toArray(), function ($carry, $reservation) {
            return $carry + $reservation->getNumberOfTickets();
        }, 0);

        if ($totalReservations + $numberOfTickets > $event->getNbrPlace()) {
            $this->addFlash('error', 'L\'événement est complet.');
            return $this->redirectToRoute('app_event1_show', ['id' => $id]);
        }

        // Create a new reservation
        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setUser($user);
        $reservation->setNumberOfTickets($numberOfTickets);

        // Save the reservation
        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->redirectToRoute('app_event1_show', ['id' => $id]);
    }
}
