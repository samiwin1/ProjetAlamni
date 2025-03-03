<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303002016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation_favorites (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F93E3E2E9AC0396 (conversation_id), INDEX IDX_F93E3E2EA76ED395 (user_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conversation_favorites ADD CONSTRAINT FK_F93E3E2E9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_favorites ADD CONSTRAINT FK_F93E3E2EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation_favorites DROP FOREIGN KEY FK_F93E3E2E9AC0396');
        $this->addSql('ALTER TABLE conversation_favorites DROP FOREIGN KEY FK_F93E3E2EA76ED395');
        $this->addSql('DROP TABLE conversation_favorites');
    }
}
