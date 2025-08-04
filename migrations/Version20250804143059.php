<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804143059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cancellation DROP FOREIGN KEY FK_FBCE5D0CA76ED395');
        $this->addSql('DROP INDEX IDX_FBCE5D0CA76ED395 ON cancellation');
        $this->addSql('ALTER TABLE cancellation DROP user_id, DROP user, CHANGE concellationreason cancellationreason VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cancellation ADD user_id INT NOT NULL, ADD user VARCHAR(50) NOT NULL, CHANGE cancellationreason concellationreason VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE cancellation ADD CONSTRAINT FK_FBCE5D0CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_FBCE5D0CA76ED395 ON cancellation (user_id)');
    }
}
