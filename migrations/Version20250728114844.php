<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728114844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE older (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE older_category (older_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_A955511ABA7E8AAB (older_id), INDEX IDX_A955511A12469DE2 (category_id), PRIMARY KEY(older_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE older_category ADD CONSTRAINT FK_A955511ABA7E8AAB FOREIGN KEY (older_id) REFERENCES older (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE older_category ADD CONSTRAINT FK_A955511A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE older_category DROP FOREIGN KEY FK_A955511ABA7E8AAB');
        $this->addSql('ALTER TABLE older_category DROP FOREIGN KEY FK_A955511A12469DE2');
        $this->addSql('DROP TABLE older');
        $this->addSql('DROP TABLE older_category');
    }
}
