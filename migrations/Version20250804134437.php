<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804134437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cancellation (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, concellationreason VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_FBCE5D0C4A7E4868 (sale_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cancellation ADD CONSTRAINT FK_FBCE5D0C4A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cancellation DROP FOREIGN KEY FK_FBCE5D0C4A7E4868');
        $this->addSql('DROP TABLE cancellation');
    }
}
