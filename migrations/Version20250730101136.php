<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730101136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order CHANGE waiter_id waiter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3E9F3D07E FOREIGN KEY (waiter_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3B1CE6A3E9F3D07E ON customer_order (waiter_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `customer_order` DROP FOREIGN KEY FK_3B1CE6A3E9F3D07E');
        $this->addSql('DROP INDEX IDX_3B1CE6A3E9F3D07E ON `customer_order`');
        $this->addSql('ALTER TABLE `customer_order` CHANGE waiter_id waiter_id INT NOT NULL');
    }
}
