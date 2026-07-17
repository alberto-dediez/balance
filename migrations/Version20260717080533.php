<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717080533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_contribution_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              employee_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_id)',
              token VARCHAR(64) NOT NULL,
              status VARCHAR(20) NOT NULL,
              step_status VARCHAR(30) NOT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ignored_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              employee_name VARCHAR(255) DEFAULT NULL,
              worked_assembly TINYINT(1) NOT NULL,
              assembly_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
              assembly_distance_km DOUBLE PRECISION DEFAULT NULL,
              assembly_vehicle_occupants INT DEFAULT NULL,
              assembly_days INT DEFAULT NULL,
              assembly_hotel_nights INT DEFAULT NULL,
              worked_celebration TINYINT(1) NOT NULL,
              celebration_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
              celebration_distance_km DOUBLE PRECISION DEFAULT NULL,
              celebration_vehicle_occupants INT DEFAULT NULL,
              celebration_days INT DEFAULT NULL,
              celebration_hotel_nights INT DEFAULT NULL,
              worked_breakdown TINYINT(1) NOT NULL,
              breakdown_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
              breakdown_distance_km DOUBLE PRECISION DEFAULT NULL,
              breakdown_vehicle_occupants INT DEFAULT NULL,
              breakdown_days INT DEFAULT NULL,
              breakdown_hotel_nights INT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_CB6A455F37A13B (token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_participations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_participation_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              status VARCHAR(10) NOT NULL,
              total_employees INT DEFAULT NULL,
              confidence_level SMALLINT DEFAULT NULL,
              margin_of_error DOUBLE PRECISION DEFAULT NULL,
              closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_A1035E98B6931EEA (event_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE event_carbon_footprint_activities ADD from_employees TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE employee_contributions');
        $this->addSql('DROP TABLE employee_participations');
        $this->addSql('ALTER TABLE event_carbon_footprint_activities DROP from_employees');
    }
}
