<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test-email';
    private $mailer;
    private $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('Test d\'envoi via Mailtrap Symfony...');
            
            // Afficher la configuration SMTP
            $output->writeln('Configuration SMTP : ' . $_ENV['MAILER_DSN']);
    
            $testId = uniqid();
            // Utilisez des adresses email plus spécifiques
            $fromEmail = 'symfony@test.com';
            $toEmail = 'mailtrap@test.com';
    
            $email = (new Email())
                ->from(new Address($fromEmail, 'Symfony Test'))
                ->to(new Address($toEmail))
                ->subject('Symfony Mailtrap Test #' . $testId)
                ->text('Test message')
                ->html('
                    <div style="padding: 20px; background: #f5f5f5; border-radius: 5px;">
                        <h1 style="color: #e83e8c;">Test Symfony Mailtrap</h1>
                        <p>Test ID: ' . $testId . '</p>
                        <p>Date/Heure: ' . date('Y-m-d H:i:s') . '</p>
                        <p>From: ' . $fromEmail . '</p>
                        <p>To: ' . $toEmail . '</p>
                        <p>Inbox: symfony</p>
                    </div>
                ');
    
            $output->writeln('Envoi en cours...');
            $this->mailer->send($email);
            
            $output->writeln('<info>Email envoyé avec succès!</info>');
            $output->writeln('Vérifiez l\'inbox "symfony" dans Mailtrap');
            $output->writeln('ID du message : ' . $testId);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}