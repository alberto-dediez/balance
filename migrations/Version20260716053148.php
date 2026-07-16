<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716053148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_carbon_footprint_exclusions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_exclusion_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              phase VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              reason VARCHAR(255) NOT NULL,
              comment LONGTEXT DEFAULT NULL,
              excluded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_D4561C7EB6931EEA (event_carbon_footprint_id),
              UNIQUE INDEX UNIQ_ecf_exclusions_phase_source (
                event_carbon_footprint_id, phase,
                source
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_carbon_footprint_exclusions
            ADD
              CONSTRAINT FK_D4561C7EB6931EEA FOREIGN KEY (event_carbon_footprint_id) REFERENCES event_carbon_footprints (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_carbon_footprint_exclusions DROP FOREIGN KEY FK_D4561C7EB6931EEA');
        $this->addSql('DROP TABLE event_carbon_footprint_exclusions');
    }
}
