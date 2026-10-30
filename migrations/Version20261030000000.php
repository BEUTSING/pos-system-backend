<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product media, logo (company/customer images) and the invoice billing module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_media (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, path VARCHAR(255) NOT NULL, is_primary TINYINT(1) DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_PRODUCT_MEDIA_PRODUCT (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_media ADD CONSTRAINT FK_PRODUCT_MEDIA_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE logo (id INT AUTO_INCREMENT NOT NULL, company_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, is_primary TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_LOGO_COMPANY (company_id), INDEX IDX_LOGO_CUSTOMER (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE logo ADD CONSTRAINT FK_LOGO_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE logo ADD CONSTRAINT FK_LOGO_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, customer_id INT DEFAULT NULL, sale_id INT DEFAULT NULL, reference VARCHAR(50) NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, tax_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, subtotal NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, tax_amount NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, total NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, notes LONGTEXT DEFAULT NULL, issued_at DATETIME NOT NULL, due_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_INVOICE_REFERENCE (reference), UNIQUE INDEX UNIQ_INVOICE_SALE (sale_id), INDEX IDX_INVOICE_COMPANY (company_id), INDEX IDX_INVOICE_CUSTOMER (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_INVOICE_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_INVOICE_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_INVOICE_SALE FOREIGN KEY (sale_id) REFERENCES sale (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE invoice_item (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, product_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_INVOICE_ITEM_INVOICE (invoice_id), INDEX IDX_INVOICE_ITEM_PRODUCT (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_INVOICE_ITEM_INVOICE FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_INVOICE_ITEM_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE customer ADD picture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer DROP picture');

        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_INVOICE_ITEM_INVOICE');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_INVOICE_ITEM_PRODUCT');
        $this->addSql('DROP TABLE invoice_item');

        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_INVOICE_COMPANY');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_INVOICE_CUSTOMER');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_INVOICE_SALE');
        $this->addSql('DROP TABLE invoice');

        $this->addSql('ALTER TABLE logo DROP FOREIGN KEY FK_LOGO_COMPANY');
        $this->addSql('ALTER TABLE logo DROP FOREIGN KEY FK_LOGO_CUSTOMER');
        $this->addSql('DROP TABLE logo');

        $this->addSql('ALTER TABLE product_media DROP FOREIGN KEY FK_PRODUCT_MEDIA_PRODUCT');
        $this->addSql('DROP TABLE product_media');
    }
}
