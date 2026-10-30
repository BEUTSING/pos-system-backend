<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company invoice settings, product details, and customer links to orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE company ADD address VARCHAR(255) DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD currency VARCHAR(3) DEFAULT 'MAD' NOT NULL, ADD logo LONGTEXT DEFAULT NULL, ADD clients_enabled TINYINT(1) DEFAULT 1 NOT NULL, ADD suppliers_enabled TINYINT(1) DEFAULT 1 NOT NULL, CHANGE site_web site_web VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE customer ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3B1CE6A39395C3F3 ON customer_order (customer_id)');
        $this->addSql("ALTER TABLE product ADD reference VARCHAR(100) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD unit VARCHAR(30) DEFAULT 'pcs' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A39395C3F3');
        $this->addSql('DROP INDEX IDX_3B1CE6A39395C3F3 ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP customer_id');
        $this->addSql('ALTER TABLE supplier DROP address');
        $this->addSql('ALTER TABLE customer DROP address');
        $this->addSql('ALTER TABLE product DROP reference, DROP description, DROP unit');
        $this->addSql('ALTER TABLE company DROP address, DROP country, DROP currency, DROP logo, DROP clients_enabled, DROP suppliers_enabled, CHANGE site_web site_web VARCHAR(255) NOT NULL');
    }
}
