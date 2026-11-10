<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110062601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_likes (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, artist_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, UNIQUE INDEX uq_artist_user (artist_id, user_id), INDEX IDX_29C67E2EB7970CF8 (artist_id), INDEX IDX_29C67E2EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE artist_likes ADD CONSTRAINT FK_29C67E2EB7970CF8 FOREIGN KEY (artist_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist_likes ADD CONSTRAINT FK_29C67E2EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_likes DROP FOREIGN KEY FK_29C67E2EB7970CF8');
        $this->addSql('ALTER TABLE artist_likes DROP FOREIGN KEY FK_29C67E2EA76ED395');
        $this->addSql('DROP TABLE artist_likes');
    }
}
