<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313122548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE containers (
          id VARCHAR(36) NOT NULL,
          purpose LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
          measurement_method LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
          removed TINYINT(1) DEFAULT NULL,
          events LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\',
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          property_id VARCHAR(36) DEFAULT NULL,
          waste_id VARCHAR(36) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE containers');
    }
}
