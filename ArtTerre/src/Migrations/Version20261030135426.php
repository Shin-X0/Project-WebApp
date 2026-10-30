<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030135426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artworks (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, category VARCHAR(30) DEFAULT \'painting\' NOT NULL, story LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, status VARCHAR(10) DEFAULT \'available\' NOT NULL, created_at DATETIME NOT NULL, artist_id INT UNSIGNED NOT NULL, INDEX IDX_A2E004C7B7970CF8 (artist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart_items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, artist VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, qty SMALLINT UNSIGNED DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, user_id INT UNSIGNED NOT NULL, artwork_id INT UNSIGNED DEFAULT NULL, INDEX IDX_BEF48445A76ED395 (user_id), INDEX IDX_BEF48445DB8FFA4 (artwork_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_messages (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, message LONGTEXT NOT NULL, reply LONGTEXT DEFAULT NULL, replied_at DATETIME DEFAULT NULL, is_read TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, user_id INT UNSIGNED DEFAULT NULL, replied_by INT UNSIGNED DEFAULT NULL, INDEX IDX_41278201A76ED395 (user_id), INDEX IDX_41278201341B21F2 (replied_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, artist VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, qty INT UNSIGNED DEFAULT 1 NOT NULL, order_id INT UNSIGNED NOT NULL, artwork_id INT UNSIGNED DEFAULT NULL, artist_id INT UNSIGNED DEFAULT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), INDEX IDX_62809DB0DB8FFA4 (artwork_id), INDEX IDX_62809DB0B7970CF8 (artist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT UNSIGNED AUTO_INCREMENT NOT NULL, order_no VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, zip VARCHAR(20) DEFAULT NULL, payment_method VARCHAR(10) DEFAULT \'cod\' NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, user_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_E52FFDEE550C2277 (order_no), INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL, dob DATE DEFAULT NULL, gender VARCHAR(20) DEFAULT NULL, role VARCHAR(20) DEFAULT \'collector\' NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE artworks ADD CONSTRAINT FK_A2E004C7B7970CF8 FOREIGN KEY (artist_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF48445A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF48445DB8FFA4 FOREIGN KEY (artwork_id) REFERENCES artworks (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cart_items ADD UNIQUE KEY uq_user_cart (user_id, artwork_id)');
        $this->addSql('ALTER TABLE contact_messages ADD CONSTRAINT FK_41278201A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contact_messages ADD CONSTRAINT FK_41278201341B21F2 FOREIGN KEY (replied_by) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB0DB8FFA4 FOREIGN KEY (artwork_id) REFERENCES artworks (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB0B7970CF8 FOREIGN KEY (artist_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artworks DROP FOREIGN KEY FK_A2E004C7B7970CF8');
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF48445A76ED395');
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF48445DB8FFA4');
        $this->addSql('ALTER TABLE contact_messages DROP FOREIGN KEY FK_41278201A76ED395');
        $this->addSql('ALTER TABLE contact_messages DROP FOREIGN KEY FK_41278201341B21F2');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB0DB8FFA4');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB0B7970CF8');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('DROP TABLE artworks');
        $this->addSql('DROP TABLE cart_items');
        $this->addSql('DROP TABLE contact_messages');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
