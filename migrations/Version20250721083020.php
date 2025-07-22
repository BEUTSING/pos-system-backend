<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250721083020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09E7A1254A');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09E7A1254A FOREIGN KEY (contact_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660D5C01451');
        $this->addSql('DROP INDEX IDX_4B365660D5C01451 ON stock');
        $this->addSql('ALTER TABLE stock CHANGE productst_id product_stock_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660C67D0B87 FOREIGN KEY (product_stock_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_4B365660C67D0B87 ON stock (product_stock_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660C67D0B87');
        $this->addSql('DROP INDEX IDX_4B365660C67D0B87 ON stock');
        $this->addSql('ALTER TABLE stock CHANGE product_stock_id productst_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660D5C01451 FOREIGN KEY (productst_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_4B365660D5C01451 ON stock (productst_id)');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09E7A1254A');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
