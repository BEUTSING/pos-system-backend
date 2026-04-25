<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425171515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762DA76ED395');
        $this->addSql('DROP TABLE log_entry');
        $this->addSql('ALTER TABLE category ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_64C19C1979B1AD6 ON category (company_id)');
        $this->addSql('ALTER TABLE company ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE customer ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_81398E09979B1AD6 ON customer (company_id)');
        $this->addSql('ALTER TABLE customer_order ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_3B1CE6A3979B1AD6 ON customer_order (company_id)');
        $this->addSql('ALTER TABLE orderitem ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE orderitem ADD CONSTRAINT FK_112B7384979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_112B7384979B1AD6 ON orderitem (company_id)');
        $this->addSql('ALTER TABLE product ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD979B1AD6 ON product (company_id)');
        $this->addSql('ALTER TABLE sale ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC005979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_E54BC005979B1AD6 ON sale (company_id)');
        $this->addSql('ALTER TABLE stockmovement ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE stockmovement ADD CONSTRAINT FK_99336429979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_99336429979B1AD6 ON stockmovement (company_id)');
        $this->addSql('ALTER TABLE supplier ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7E979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_9B2A6C7E979B1AD6 ON supplier (company_id)');
        $this->addSql('ALTER TABLE user ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_entry (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B5F762DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1979B1AD6');
        $this->addSql('DROP INDEX IDX_64C19C1979B1AD6 ON category');
        $this->addSql('ALTER TABLE category DROP company_id');
        $this->addSql('ALTER TABLE company DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09979B1AD6');
        $this->addSql('DROP INDEX IDX_81398E09979B1AD6 ON customer');
        $this->addSql('ALTER TABLE customer DROP company_id');
        $this->addSql('ALTER TABLE `customer_order` DROP FOREIGN KEY FK_3B1CE6A3979B1AD6');
        $this->addSql('DROP INDEX IDX_3B1CE6A3979B1AD6 ON `customer_order`');
        $this->addSql('ALTER TABLE `customer_order` DROP company_id');
        $this->addSql('ALTER TABLE `orderitem` DROP FOREIGN KEY FK_112B7384979B1AD6');
        $this->addSql('DROP INDEX IDX_112B7384979B1AD6 ON `orderitem`');
        $this->addSql('ALTER TABLE `orderitem` DROP company_id');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD979B1AD6');
        $this->addSql('DROP INDEX IDX_D34A04AD979B1AD6 ON product');
        $this->addSql('ALTER TABLE product DROP company_id');
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005979B1AD6');
        $this->addSql('DROP INDEX IDX_E54BC005979B1AD6 ON sale');
        $this->addSql('ALTER TABLE sale DROP company_id');
        $this->addSql('ALTER TABLE stockmovement DROP FOREIGN KEY FK_99336429979B1AD6');
        $this->addSql('DROP INDEX IDX_99336429979B1AD6 ON stockmovement');
        $this->addSql('ALTER TABLE stockmovement DROP company_id');
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7E979B1AD6');
        $this->addSql('DROP INDEX IDX_9B2A6C7E979B1AD6 ON supplier');
        $this->addSql('ALTER TABLE supplier DROP company_id');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('DROP INDEX IDX_8D93D649979B1AD6 ON user');
        $this->addSql('ALTER TABLE user DROP company_id');
    }
}
