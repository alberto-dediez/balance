<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261028153405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_employee_contribution_commutes (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_contribution_commute_id)',
              contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_contribution_id)',
              mobility_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mobility_id)',
              distance_km DOUBLE PRECISION NOT NULL,
              days_per_week SMALLINT NOT NULL,
              vehicle_occupants SMALLINT NOT NULL,
              INDEX IDX_895606BBFE5E5FBD (contribution_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_employee_contribution_trips (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_contribution_trip_id)',
              contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_contribution_id)',
              mobility_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mobility_id)',
              distance_km DOUBLE PRECISION NOT NULL,
              trips SMALLINT NOT NULL,
              vehicle_occupants SMALLINT NOT NULL,
              hotel_nights SMALLINT NOT NULL,
              INDEX IDX_E4600088FE5E5FBD (contribution_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_employee_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_contribution_id)',
              subject_type VARCHAR(20) NOT NULL,
              carbon_footprint_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              employee_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_id)',
              employee_name VARCHAR(255) DEFAULT NULL,
              token VARCHAR(64) NOT NULL,
              status VARCHAR(20) NOT NULL,
              step_status VARCHAR(30) NOT NULL,
              workplace_property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              commuting_weeks_per_year SMALLINT DEFAULT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ignored_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              consent_policy_version VARCHAR(64) DEFAULT NULL,
              consent_locale VARCHAR(5) DEFAULT NULL,
              consent_ip VARCHAR(45) DEFAULT NULL,
              consent_user_agent VARCHAR(512) DEFAULT NULL,
              consent_accept_language VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX UNIQ_89E9E4675F37A13B (token),
              INDEX IDX_oec_footprint (
                subject_type, carbon_footprint_id
              ),
              INDEX IDX_oec_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_employee_participations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_employee_participation_id)',
              subject_type VARCHAR(20) NOT NULL,
              carbon_footprint_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              total_employees INT DEFAULT NULL,
              confidence_level SMALLINT DEFAULT NULL,
              margin_of_error DOUBLE PRECISION DEFAULT NULL,
              answered_count INT DEFAULT NULL,
              aggregated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_oep_account (account_id),
              UNIQUE INDEX UNIQ_oep_footprint (
                subject_type, carbon_footprint_id
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              organizational_employee_contribution_commutes
            ADD
              CONSTRAINT FK_895606BBFE5E5FBD FOREIGN KEY (contribution_id) REFERENCES organizational_employee_contributions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              organizational_employee_contribution_trips
            ADD
              CONSTRAINT FK_E4600088FE5E5FBD FOREIGN KEY (contribution_id) REFERENCES organizational_employee_contributions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_activities
            ADD
              from_employees TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_activities
            ADD
              from_employees TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organizational_employee_contribution_commutes DROP FOREIGN KEY FK_895606BBFE5E5FBD');
        $this->addSql('ALTER TABLE organizational_employee_contribution_trips DROP FOREIGN KEY FK_E4600088FE5E5FBD');
        $this->addSql('DROP TABLE organizational_employee_contribution_commutes');
        $this->addSql('DROP TABLE organizational_employee_contribution_trips');
        $this->addSql('DROP TABLE organizational_employee_contributions');
        $this->addSql('DROP TABLE organizational_employee_participations');
        $this->addSql('ALTER TABLE company_carbon_footprint_activities DROP from_employees');
        $this->addSql('ALTER TABLE property_carbon_footprint_activities DROP from_employees');
    }
}
