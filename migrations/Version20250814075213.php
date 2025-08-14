<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250814075213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX daily_wastes_search_idx ON daily_wastes');
        $this->addSql('ALTER TABLE
          daily_wastes
        CHANGE
          registered_at day DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX daily_wastes_search_idx ON daily_wastes (
          property_id, account_id, waste_id,
          purpose, waste_manager_id, day
        )');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX daily_wastes_search_idx ON daily_wastes');
        $this->addSql('ALTER TABLE
          daily_wastes
        CHANGE
          day registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX daily_wastes_search_idx ON daily_wastes (
          property_id, account_id, waste_id,
          purpose, waste_manager_id
        )');
    }
}
