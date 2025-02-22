<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220222753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, user_email VARCHAR(255) NOT NULL, admin_mail VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, objet VARCHAR(255) NOT NULL, description VARCHAR(2000) NOT NULL, date_soumission DATETIME NOT NULL, status VARCHAR(255) DEFAULT \'En attente\' NOT NULL, INDEX IDX_CE606404A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE planning ADD teacher_id INT DEFAULT NULL, ADD student_level VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF641807E1D FOREIGN KEY (teacher_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D499BFF641807E1D ON planning (teacher_id)');
        $this->addSql('ALTER TABLE user ADD photo LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404A76ED395');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF641807E1D');
        $this->addSql('DROP INDEX IDX_D499BFF641807E1D ON planning');
        $this->addSql('ALTER TABLE planning DROP teacher_id, DROP student_level');
        $this->addSql('ALTER TABLE user DROP photo');
    }
}
