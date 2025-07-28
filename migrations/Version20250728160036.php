<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728160036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `customer_order` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orderitem (id INT AUTO_INCREMENT NOT NULL, customer_order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_112B7384A15A2E17 (customer_order_id), INDEX IDX_112B73844584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orderitem ADD CONSTRAINT FK_112B7384A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES `customer_order` (id)');
        $this->addSql('ALTER TABLE orderitem ADD CONSTRAINT FK_112B73844584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orderitem DROP FOREIGN KEY FK_112B7384A15A2E17');
        $this->addSql('ALTER TABLE orderitem DROP FOREIGN KEY FK_112B73844584665A');
        $this->addSql('DROP TABLE `customer_order`');
        $this->addSql('DROP TABLE orderitem');
    }
}
