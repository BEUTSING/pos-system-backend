<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801142347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale ADD teller_id INT NOT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC005E9894D10 FOREIGN KEY (teller_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E54BC005E9894D10 ON sale (teller_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005E9894D10');
        $this->addSql('DROP INDEX IDX_E54BC005E9894D10 ON sale');
        $this->addSql('ALTER TABLE sale DROP teller_id');
    }
}
