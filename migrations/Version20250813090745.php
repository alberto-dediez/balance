<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813090745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE daily_wastes (
          id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:daily_waste_id)\',
          property_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:property_id)\',
          account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\',
          waste_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_id)\',
          purpose VARCHAR(255) NOT NULL,
          waste_manager_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_manager_id)\',
          container_load NUMERIC(14, 4) NOT NULL,
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          UNIQUE INDEX daily_wastes_search_idx (
            property_id, account_id, waste_id,
            purpose, waste_manager_id
          ),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE daily_wastes');
    }
}
