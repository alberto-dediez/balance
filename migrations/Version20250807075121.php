<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250807075121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          registered_by registered_by VARCHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
        CHANGE
          waste_manager_id waste_manager_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_manager_id)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          waste_manager_id waste_manager_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          registered_by registered_by VARCHAR(36) NOT NULL');
    }
}
