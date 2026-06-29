<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629110440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_id)',
              name VARCHAR(255) NOT NULL,
              location_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:destination_id)',
              size VARCHAR(255) DEFAULT NULL,
              estimated_attendees INT DEFAULT NULL,
              sector VARCHAR(255) DEFAULT NULL,
              typology VARCHAR(255) DEFAULT NULL,
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              removed TINYINT(1) DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              setup_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              setup_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              celebration_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              celebration_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              dismantling_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              dismantling_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE events');
    }
}
