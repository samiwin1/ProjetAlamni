<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303220235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A32EFC6');
        $this->addSql('CREATE TABLE ratting (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, ratting INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_A292F13A71F7E88B (event_id), INDEX IDX_A292F13AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ratting ADD CONSTRAINT FK_A292F13A71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE ratting ADD CONSTRAINT FK_A292F13AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622A76ED395');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D889262271F7E88B');
        $this->addSql('DROP TABLE rating');
        $this->addSql('DROP INDEX IDX_3BAE0AA7A32EFC6 ON event');
        $this->addSql('ALTER TABLE event CHANGE rating_id ratting_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA75C1B8740 FOREIGN KEY (ratting_id) REFERENCES ratting (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA75C1B8740 ON event (ratting_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA75C1B8740');
        $this->addSql('CREATE TABLE rating (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_D889262271F7E88B (event_id), INDEX IDX_D8892622A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D889262271F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE ratting DROP FOREIGN KEY FK_A292F13A71F7E88B');
        $this->addSql('ALTER TABLE ratting DROP FOREIGN KEY FK_A292F13AA76ED395');
        $this->addSql('DROP TABLE ratting');
        $this->addSql('DROP INDEX IDX_3BAE0AA75C1B8740 ON event');
        $this->addSql('ALTER TABLE event CHANGE ratting_id rating_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A32EFC6 FOREIGN KEY (rating_id) REFERENCES rating (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A32EFC6 ON event (rating_id)');
    }
}
