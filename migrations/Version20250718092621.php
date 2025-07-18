<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250718092621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, categoryname VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, phone INT NOT NULL, city VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, INDEX IDX_81398E09E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, namedoc VARCHAR(255) NOT NULL, typedoc VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, productname_id INT DEFAULT NULL, saledate_id INT DEFAULT NULL, customernam_id INT DEFAULT NULL, customername VARCHAR(255) NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 6) NOT NULL, totaux NUMERIC(10, 6) NOT NULL, INDEX IDX_90651744EA583AF1 (productname_id), INDEX IDX_9065174435C5DF2A (saledate_id), INDEX IDX_90651744332F3B2F (customernam_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, shelf_id INT DEFAULT NULL, productname VARCHAR(255) NOT NULL, purchaseprice NUMERIC(10, 2) NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04AD7C12FBC0 (shelf_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE purchaseorder (id INT AUTO_INCREMENT NOT NULL, productname_id INT DEFAULT NULL, suppliername VARCHAR(255) NOT NULL, orderdate DATE NOT NULL, duedate DATE NOT NULL, INDEX IDX_D8BF2BE0EA583AF1 (productname_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sale (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, quantity INT NOT NULL, saleprice NUMERIC(10, 2) NOT NULL, total INT NOT NULL, datesale VARCHAR(255) NOT NULL, INDEX IDX_E54BC005F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shelf (id INT AUTO_INCREMENT NOT NULL, names VARCHAR(255) NOT NULL, number INT NOT NULL, quantityshelf NUMERIC(10, 7) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, productst_id INT DEFAULT NULL, quantityst INT NOT NULL, minimumstock INT NOT NULL, INDEX IDX_4B365660D5C01451 (productst_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stockmovement (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, quantity INT NOT NULL, typemovement VARCHAR(50) NOT NULL, datemovement DATE NOT NULL, reason VARCHAR(255) NOT NULL, INDEX IDX_993364294584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, INDEX IDX_9B2A6C7EE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744EA583AF1 FOREIGN KEY (productname_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174435C5DF2A FOREIGN KEY (saledate_id) REFERENCES sale (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744332F3B2F FOREIGN KEY (customernam_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelf (id)');
        $this->addSql('ALTER TABLE purchaseorder ADD CONSTRAINT FK_D8BF2BE0EA583AF1 FOREIGN KEY (productname_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC005F347EFB FOREIGN KEY (produit_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660D5C01451 FOREIGN KEY (productst_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stockmovement ADD CONSTRAINT FK_993364294584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7EE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09E7A1254A');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744EA583AF1');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_9065174435C5DF2A');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744332F3B2F');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7C12FBC0');
        $this->addSql('ALTER TABLE purchaseorder DROP FOREIGN KEY FK_D8BF2BE0EA583AF1');
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005F347EFB');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660D5C01451');
        $this->addSql('ALTER TABLE stockmovement DROP FOREIGN KEY FK_993364294584665A');
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7EE7A1254A');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE purchaseorder');
        $this->addSql('DROP TABLE sale');
        $this->addSql('DROP TABLE shelf');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE stockmovement');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
