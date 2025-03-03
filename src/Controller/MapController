<?php
namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapController extends AbstractController
{
    #[Route('/mapb', name: 'app_mapb')]
    public function map(EventRepository $eventRepository): Response
    {
        $event = $eventRepository->findAll();
        $markers = [];
        
        foreach ($event as $ev) {
            $markers[] = [
                'latitude' => $ev->getLatitude(),
                'longitude' => $ev->getLongitude()
            ];
        }
        
        return $this->render('map/mapb.html.twig', [
            'markers' => $markers,
        ]);
    }
    #[Route('/mapf', name: 'app_map')]
    public function mapf(EventRepository $eventRepository): Response
    {
        $event = $eventRepository->findAll();
        $markers = [];
        
        foreach ($event as $ev) {
            $markers[] = [
                'latitude' => $ev->getLatitude(),
                'longitude' => $ev->getLongitude()
            ];
        }
        
        return $this->render('map/mapf.html.twig', [
            'markers' => $markers,
        ]);
    }
}