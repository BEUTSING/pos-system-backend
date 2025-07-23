<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250723144914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005F347EFB');
        $this->addSql('DROP INDEX IDX_E54BC005F347EFB ON sale');
        $this->addSql('ALTER TABLE sale CHANGE produit_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC0054584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_E54BC0054584665A ON sale (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC0054584665A');
        $this->addSql('DROP INDEX IDX_E54BC0054584665A ON sale');
        $this->addSql('ALTER TABLE sale CHANGE product_id produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC005F347EFB FOREIGN KEY (produit_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E54BC005F347EFB ON sale (produit_id)');
    }
}
