<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806085612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE trip_calculation_legs (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_leg_id)',
              trip_calculation_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_id)',
              position INT NOT NULL,
              kind VARCHAR(20) NOT NULL,
              mobility_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mobility_id)',
              vehicle_occupants INT DEFAULT NULL,
              origin_place_reference VARCHAR(255) DEFAULT NULL,
              origin_label VARCHAR(255) DEFAULT NULL,
              origin_latitude DOUBLE PRECISION DEFAULT NULL,
              origin_longitude DOUBLE PRECISION DEFAULT NULL,
              destination_place_reference VARCHAR(255) DEFAULT NULL,
              destination_label VARCHAR(255) DEFAULT NULL,
              destination_latitude DOUBLE PRECISION DEFAULT NULL,
              destination_longitude DOUBLE PRECISION DEFAULT NULL,
              distance_km DOUBLE PRECISION DEFAULT NULL,
              distance_source VARCHAR(20) NOT NULL,
              INDEX IDX_E3A5E7E88FB0EFE1 (trip_calculation_id),
              INDEX idx_trip_calculation_legs_trip_calculation (trip_calculation_id, position),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trip_calculation_links (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              token VARCHAR(64) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_72201CC8549213EC (property_id),
              UNIQUE INDEX UNIQ_72201CC85F37A13B (token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trip_calculations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_id)',
              trip_calculation_link_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_id)',
              origin_place_reference VARCHAR(255) DEFAULT NULL,
              origin_label VARCHAR(255) DEFAULT NULL,
              origin_latitude DOUBLE PRECISION DEFAULT NULL,
              origin_longitude DOUBLE PRECISION DEFAULT NULL,
              round_trip TINYINT(1) NOT NULL,
              trips INT DEFAULT NULL,
              nights INT DEFAULT NULL,
              emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              calculation_year INT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              emissions_co2eq VARCHAR(255) NOT NULL,
              emissions_co2 VARCHAR(255) NOT NULL,
              emissions_ch4 VARCHAR(255) NOT NULL,
              emissions_n2o VARCHAR(255) NOT NULL,
              mobility_emissions_co2eq VARCHAR(255) NOT NULL,
              mobility_emissions_co2 VARCHAR(255) NOT NULL,
              mobility_emissions_ch4 VARCHAR(255) NOT NULL,
              mobility_emissions_n2o VARCHAR(255) NOT NULL,
              overnight_emissions_co2eq VARCHAR(255) NOT NULL,
              overnight_emissions_co2 VARCHAR(255) NOT NULL,
              overnight_emissions_ch4 VARCHAR(255) NOT NULL,
              overnight_emissions_n2o VARCHAR(255) NOT NULL,
              INDEX idx_trip_calculations_link_id (trip_calculation_link_id),
              INDEX idx_trip_calculations_registered_at (registered_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculation_legs
            ADD
              CONSTRAINT FK_E3A5E7E88FB0EFE1 FOREIGN KEY (trip_calculation_id) REFERENCES trip_calculations (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip_calculation_legs DROP FOREIGN KEY FK_E3A5E7E88FB0EFE1');
        $this->addSql('DROP TABLE trip_calculation_legs');
        $this->addSql('DROP TABLE trip_calculation_links');
        $this->addSql('DROP TABLE trip_calculations');
    }
}
