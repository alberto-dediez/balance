<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729222546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brands (
          id VARCHAR(36) NOT NULL,
          name VARCHAR(255) NOT NULL,
          removed TINYINT(1) DEFAULT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          hotelbeds VARCHAR(36) DEFAULT NULL,
          restel VARCHAR(36) DEFAULT NULL,
          bioscore VARCHAR(36) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories (
          id VARCHAR(36) NOT NULL,
          name VARCHAR(255) NOT NULL,
          removed TINYINT(1) DEFAULT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          hotelbeds VARCHAR(36) DEFAULT NULL,
          restel VARCHAR(36) DEFAULT NULL,
          bioscore VARCHAR(36) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE countries (
          id VARCHAR(36) NOT NULL,
          name VARCHAR(255) NOT NULL,
          iso VARCHAR(10) DEFAULT NULL,
          removed TINYINT(1) DEFAULT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          hotelbeds VARCHAR(36) DEFAULT NULL,
          restel VARCHAR(36) DEFAULT NULL,
          bioscore VARCHAR(36) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE destinations (
          id VARCHAR(36) NOT NULL,
          name VARCHAR(255) NOT NULL,
          country_id VARCHAR(36) DEFAULT NULL,
          removed TINYINT(1) DEFAULT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          hotelbeds VARCHAR(36) DEFAULT NULL,
          restel VARCHAR(36) DEFAULT NULL,
          bioscore VARCHAR(36) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          properties
        ADD
          property_type VARCHAR(255) DEFAULT NULL,
        ADD
          category_id VARCHAR(36) DEFAULT NULL,
        ADD
          brand_id VARCHAR(36) DEFAULT NULL,
        ADD
          address VARCHAR(1024) DEFAULT NULL,
        ADD
          postal_code VARCHAR(20) DEFAULT NULL,
        ADD
          longitude NUMERIC(14, 10) DEFAULT NULL,
        ADD
          latitude NUMERIC(14, 10) DEFAULT NULL,
        ADD
          rooms INT DEFAULT NULL,
        ADD
          zone VARCHAR(512) DEFAULT NULL,
        ADD
          web VARCHAR(1024) DEFAULT NULL,
        ADD
          destination_id VARCHAR(36) DEFAULT NULL,
        ADD
          country_id VARCHAR(36) DEFAULT NULL,
        ADD
          hotelbeds VARCHAR(36) DEFAULT NULL,
        ADD
          restel VARCHAR(36) DEFAULT NULL,
        ADD
          bioscore VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE brands');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE countries');
        $this->addSql('DROP TABLE destinations');
        $this->addSql('ALTER TABLE
          properties
        DROP
          property_type,
        DROP
          category_id,
        DROP
          brand_id,
        DROP
          address,
        DROP
          postal_code,
        DROP
          longitude,
        DROP
          latitude,
        DROP
          rooms,
        DROP
          zone,
        DROP
          web,
        DROP
          destination_id,
        DROP
          country_id,
        DROP
          hotelbeds,
        DROP
          restel,
        DROP
          bioscore');
    }
}
