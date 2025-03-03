<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Devoir;
use App\Entity\Rating;
use App\Form\RatingType;

use App\Repository\CoursRepository;
use App\Repository\DevoirRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/client')]
final class ClientController extends AbstractController
{
    private BuilderInterface $qrCodeBuilder;

    public function __construct(BuilderInterface $qrCodeBuilder)
    {
        $this->qrCodeBuilder = $qrCodeBuilder;
    }

    #[Route(name: 'app_client')]
    public function index(): Response
    {
        return $this->render('client/index.html.twig');
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

    #[Route('/course/{id}', name: 'app_course_show')]
public function show(
    Cours $cour, 
    DevoirRepository $devoirRepository, 
    CoursRepository $coursRepository,
    Request $request, 
    EntityManagerInterface $entityManager
): Response {
    // Get the assignments related to the course
    $devoirs = $devoirRepository->findBy(['cours' => $cour]);

    // Get related courses
    $relatedCourses = $coursRepository->findBy(
        ['matiereC' => $cour->getMatiereC()],
        null,
        3
    );

    // Create new rating form only if user is logged in
    $rating_form = null;
    $user = $this->getUser();
    if ($user) {
        $rating = new Rating();
        $rating->setCours($cour);
        $rating->setUser($user);
        
        $rating_form = $this->createForm(RatingType::class, $rating, [
            'action' => $this->generateUrl('app_rating_new', ['id' => $cour->getId()]),
            'method' => 'POST',
        ]);
    }

    return $this->render('client/showcourse.html.twig', [
        'cour' => $cour,
        'devoirs' => $devoirs,
        'relatedCourses' => $relatedCourses,
        'rating_form' => $rating_form ? $rating_form->createView() : null,
    ]);
}       
    #[Route('/devoir/{id}', name: 'app_devoir2_show')]
    public function showDevoir(Devoir $devoir): Response
    {
        return $this->render('client/devoirs.html.twig', [
            'devoir' => $devoir,
        ]);
    }

    #[Route('/course/{id}/devoirs', name: 'app_devoirs_by_course')]
    public function getDevoirsByCourse(Cours $cour, DevoirRepository $devoirRepository): Response
    {
        $devoirs = $devoirRepository->findBy(['cours' => $cour]);

        return $this->render('client/devoirs.html.twig', [
            'cour' => $cour,
            'devoirs' => $devoirs,
        ]);
    }

    #[Route('/{id}', name: 'app_devoir_show11', methods: ['GET'])]
    public function show1(Devoir $devoir): Response
    {
        return $this->render('client/show.html.twig', [
            'devoir' => $devoir,
        ]);
    }

    #[Route('/course/{id}/qr-code', name: 'app_course_qr_code', methods: ['GET'])]
    public function generateQrCode(Cours $cour): Response
    {
        // Vérification de l'extension GD
        if (!extension_loaded('gd')) {
            return new JsonResponse([
                'error' => 'L\'extension GD n\'est pas activée. Veuillez l\'activer dans votre configuration PHP.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // Vérification des fonctions GD nécessaires
        if (!function_exists('imagecreate') || !function_exists('imagepng')) {
            return new JsonResponse([
                'error' => 'Les fonctions GD nécessaires ne sont pas disponibles. Vérifiez la configuration de l\'extension GD.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Prepare QR code data
            $qrData = sprintf(
                "Cours ID: %d\nTitre: %s\nMatière: %s\nDate: %s\nNiveau: %s",
                $cour->getId(),
                $cour->getTitre(),
                $cour->getMatiereC(),
                $cour->getDateC()->format('d/m/Y H:i'),
                $cour->getNiveau()
            );

            // Créer un QR code directement
            $qrCode = new QrCode($qrData);
            
            // Créer un writer pour le PNG
            $writer = new PngWriter();
            
            // Générer l'image QR code
            $result = $writer->write($qrCode);
            
            // Return as a PNG response
            return new Response(
                $result->getString(),
                Response::HTTP_OK,
                ['Content-Type' => $result->getMimeType()]
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du QR code: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}