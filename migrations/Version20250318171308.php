<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318171308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE measurements (
          id VARCHAR(36) NOT NULL,
          container_id VARCHAR(36) DEFAULT NULL,
          waste_id VARCHAR(36) DEFAULT NULL,
          purpose LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
          measurement_method LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
          quantity INT NOT NULL,
          `load` NUMERIC(4, 14) NOT NULL,
          justification LONGTEXT DEFAULT NULL,
          registered_by VARCHAR(36) NOT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE measurements');
    }
}
