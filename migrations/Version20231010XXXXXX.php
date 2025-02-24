<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231010XXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create favorite table for many-to-many relationship between User and Event';
    }

    public function up(Schema $schema): void
    {
        // Create favorite table
        $this->addSql('CREATE TABLE favorite (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_8C9F3610A76ED395 (user_id),
            INDEX IDX_8C9F361071F7E88B (event_id),
            CONSTRAINT FK_8C9F3610A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_8C9F361071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE
        )');
    }

    public function down(Schema $schema): void
    {
        // Drop favorite table
        $this->addSql('DROP TABLE favorite');
    }
}
