<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailAddress = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $emailAddress]);
            if (!$user) {
                $this->addFlash('error', 'Aucun utilisateur trouvé pour cette adresse.');
            } else {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $entityManager->flush();

                $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $email = (new Email())
                    ->from('no-reply@example.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                ->html("<p>Cliquez sur ce lien pour réinitialiser votre mot de passe: <a href=\"$resetLink\">Réinitialiser le mot de passe</a></p>");
                
                // Pour envoyer l'email, décommentez la ligne suivante lorsque le mailer est configuré.
                $mailer->send($email);

                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
                return $this->redirectToRoute('app_check_email');
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
    public function reset(Request $request, EntityManagerInterface $entityManager, string $token = null): Response
    {
        if (!$token) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_forgot_password_request');
        }
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        if (!$user) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_forgot_password_request');
        }
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            } else {
                $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
                $user->setResetToken(null);
                $entityManager->flush();
                $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('backOffice/reset_password/reset.html.twig', [
            'token' => $token,
        ]);
    }
}
