<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806180544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:account_id)\'');
        $this->addSql('ALTER TABLE
          recorded_events
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:recorded_event_id)\',
        CHANGE
          user_id user_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:user_id)\'');
        $this->addSql('ALTER TABLE
          user
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE
          user
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE account CHANGE id id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE
          recorded_events
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          user_id user_id VARCHAR(36) DEFAULT NULL');
    }
}
