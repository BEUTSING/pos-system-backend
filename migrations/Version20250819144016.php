<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819144016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockmovement ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE stockmovement ADD CONSTRAINT FK_99336429A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_99336429A76ED395 ON stockmovement (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockmovement DROP FOREIGN KEY FK_99336429A76ED395');
        $this->addSql('DROP INDEX IDX_99336429A76ED395 ON stockmovement');
        $this->addSql('ALTER TABLE stockmovement DROP user_id');
    }
}
