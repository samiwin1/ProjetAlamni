<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailAddress = $request->request->get('email');
            $this->logger->info('Demande de réinitialisation pour: ' . $emailAddress);
            
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $emailAddress]);
            
            if (!$user) {
                $this->logger->warning('Utilisateur non trouvé: ' . $emailAddress);
                $this->addFlash('error', 'Aucun utilisateur trouvé pour cette adresse.');
                return $this->redirectToRoute('app_forgot_password_request');
            }
    
            try {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $entityManager->flush();
    
                // Créer le lien de réinitialisation
                $resetLink = $this->generateUrl('app_reset_password', 
                    ['token' => $token], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                
                $this->logger->info('Lien de réinitialisation généré: ' . $resetLink);
    
                // Préparer l'email
                $email = (new Email())
                    ->from(new Address('malekbensaid50@gmail.com', 'Reset Password'))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - ' . date('Y-m-d H:i:s'))
                    ->html(
                        $this->renderView('emails/reset_password.html.twig', [
                            'resetLink' => $resetLink,
                            'user' => $user
                        ])
                    );
    
                // Envoyer l'email
                $mailer->send($email);
                $this->logger->info('Email envoyé à: ' . $user->getEmail());
    
                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
                return $this->redirectToRoute('app_check_email');
    
            } catch (\Exception $e) {
                $this->logger->error('Erreur d\'envoi: ' . $e->getMessage());
                
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('error', 'Erreur: ' . $e->getMessage());
                } else {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email.');
                }
                return $this->redirectToRoute('app_forgot_password_request');
            }
        }
    
        return $this->render('backOffice/reset_password/request.html.twig');
    }

    #[Route('/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('backOffice/reset_password/check_email.html.twig');
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request, 
        EntityManagerInterface $entityManager, 
        string $token = null
    ): Response
    {
        if (!$token) {
            $this->logger->warning('Tentative de réinitialisation sans token');
            $this->addFlash('error', 'Token manquant.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->logger->warning('Token invalide utilisé: ' . $token);
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');

            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->render('backOffice/reset_password/reset.html.twig', [
                    'token' => $token
                ]);
            }

            try {
                $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
                $user->setResetToken(null);
                $entityManager->flush();

                $this->logger->info('Mot de passe réinitialisé avec succès pour: ' . $user->getEmail());
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la réinitialisation du mot de passe.');
                return $this->redirectToRoute('app_forgot_password_request');
            }
        }

        return $this->render('backOffice/reset_password/reset.html.twig', [
            'token' => $token
        ]);
    }
}