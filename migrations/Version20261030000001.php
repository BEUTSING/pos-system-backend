<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sale prices were stored without decimals, which rounded every catalogue and
 * invoice amount to the nearest unit. Align them with the purchase price scale.
 */
final class Version20261030000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store product sale prices with two decimals';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product CHANGE saleprice saleprice NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product CHANGE saleprice saleprice NUMERIC(10, 0) DEFAULT NULL');
    }
}
