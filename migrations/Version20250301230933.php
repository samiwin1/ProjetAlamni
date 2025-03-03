<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250301230933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE like_dislike (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message_id INT NOT NULL, is_liked TINYINT(1) NOT NULL, INDEX IDX_ADB6A689A76ED395 (user_id), INDEX IDX_ADB6A689537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE like_dislike ADD CONSTRAINT FK_ADB6A689A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE like_dislike ADD CONSTRAINT FK_ADB6A689537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE like_dislike DROP FOREIGN KEY FK_ADB6A689A76ED395');
        $this->addSql('ALTER TABLE like_dislike DROP FOREIGN KEY FK_ADB6A689537A1329');
        $this->addSql('DROP TABLE like_dislike');
    }
}
