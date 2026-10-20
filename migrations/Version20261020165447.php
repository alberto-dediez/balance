<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020165447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE combustible_emission_factor
              ADD well_to_tank_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_source VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE combustible_emission_factor
              SET well_to_tank_co2eq = 0, well_to_tank_co2 = 0, well_to_tank_ch4 = 0, well_to_tank_n2o = 0,
                  well_to_tank_year = year, well_to_tank_emission_factor_group_id = emission_factor_group_id, well_to_tank_unit = unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE combustible_emission_factor
              MODIFY well_to_tank_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_unit VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE machinery_fuel_emission_factor
              ADD well_to_tank_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_source VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE machinery_fuel_emission_factor
              SET well_to_tank_co2eq = 0, well_to_tank_co2 = 0, well_to_tank_ch4 = 0, well_to_tank_n2o = 0,
                  well_to_tank_year = year, well_to_tank_emission_factor_group_id = emission_factor_group_id, well_to_tank_unit = unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE machinery_fuel_emission_factor
              MODIFY well_to_tank_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_unit VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_mix_emission_factor
              ADD well_to_tank_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_source VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE electricity_mix_emission_factor
              SET well_to_tank_co2eq = 0, well_to_tank_co2 = 0, well_to_tank_ch4 = 0, well_to_tank_n2o = 0,
                  well_to_tank_year = year, well_to_tank_emission_factor_group_id = emission_factor_group_id, well_to_tank_unit = unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_mix_emission_factor
              MODIFY well_to_tank_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_unit VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_marketer_emission_factor
              ADD well_to_tank_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_source VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE electricity_marketer_emission_factor
              SET well_to_tank_co2eq = 0, well_to_tank_co2 = 0, well_to_tank_ch4 = 0, well_to_tank_n2o = 0,
                  well_to_tank_year = year, well_to_tank_emission_factor_group_id = emission_factor_group_id, well_to_tank_unit = unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_marketer_emission_factor
              MODIFY well_to_tank_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_unit VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE gdo_factor_emission_factor
              ADD well_to_tank_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_source VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE gdo_factor_emission_factor
              SET well_to_tank_co2eq = 0, well_to_tank_co2 = 0, well_to_tank_ch4 = 0, well_to_tank_n2o = 0,
                  well_to_tank_year = year, well_to_tank_emission_factor_group_id = emission_factor_group_id, well_to_tank_unit = unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE gdo_factor_emission_factor
              MODIFY well_to_tank_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_unit VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE combustible_activities
              ADD well_to_tank_emission_factor_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_emission_factor_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emission_factor_source VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2eq VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_ch4 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_n2o VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE combustible_activities x
              JOIN activities a ON a.id = x.id
              SET x.well_to_tank_emission_factor_co2eq = 0, x.well_to_tank_emission_factor_co2 = 0,
                  x.well_to_tank_emission_factor_ch4 = 0, x.well_to_tank_emission_factor_n2o = 0,
                  x.well_to_tank_emission_factor_year = a.emission_factor_year,
                  x.well_to_tank_emission_factor_emission_factor_group_id = a.emission_factor_emission_factor_group_id,
                  x.well_to_tank_emission_factor_unit = a.emission_factor_unit,
                  x.well_to_tank_emissions_co2eq = '0', x.well_to_tank_emissions_co2 = '0',
                  x.well_to_tank_emissions_ch4 = '0', x.well_to_tank_emissions_n2o = '0'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE combustible_activities
              MODIFY well_to_tank_emission_factor_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_emission_factor_unit VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2eq VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_ch4 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_n2o VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE machinery_fuel_activities
              ADD well_to_tank_emission_factor_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_emission_factor_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emission_factor_source VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2eq VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_ch4 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_n2o VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE machinery_fuel_activities x
              JOIN activities a ON a.id = x.id
              SET x.well_to_tank_emission_factor_co2eq = 0, x.well_to_tank_emission_factor_co2 = 0,
                  x.well_to_tank_emission_factor_ch4 = 0, x.well_to_tank_emission_factor_n2o = 0,
                  x.well_to_tank_emission_factor_year = a.emission_factor_year,
                  x.well_to_tank_emission_factor_emission_factor_group_id = a.emission_factor_emission_factor_group_id,
                  x.well_to_tank_emission_factor_unit = a.emission_factor_unit,
                  x.well_to_tank_emissions_co2eq = '0', x.well_to_tank_emissions_co2 = '0',
                  x.well_to_tank_emissions_ch4 = '0', x.well_to_tank_emissions_n2o = '0'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE machinery_fuel_activities
              MODIFY well_to_tank_emission_factor_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_emission_factor_unit VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2eq VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_ch4 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_n2o VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_activities
              ADD well_to_tank_emission_factor_co2eq NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_co2 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_ch4 NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_n2o NUMERIC(20, 8) DEFAULT NULL,
              ADD well_to_tank_emission_factor_year INT DEFAULT NULL,
              ADD well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              ADD well_to_tank_emission_factor_unit VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emission_factor_source VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2eq VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_co2 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_ch4 VARCHAR(255) DEFAULT NULL,
              ADD well_to_tank_emissions_n2o VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE electricity_activities x
              JOIN activities a ON a.id = x.id
              SET x.well_to_tank_emission_factor_co2eq = 0, x.well_to_tank_emission_factor_co2 = 0,
                  x.well_to_tank_emission_factor_ch4 = 0, x.well_to_tank_emission_factor_n2o = 0,
                  x.well_to_tank_emission_factor_year = a.emission_factor_year,
                  x.well_to_tank_emission_factor_emission_factor_group_id = a.emission_factor_emission_factor_group_id,
                  x.well_to_tank_emission_factor_unit = a.emission_factor_unit,
                  x.well_to_tank_emissions_co2eq = '0', x.well_to_tank_emissions_co2 = '0',
                  x.well_to_tank_emissions_ch4 = '0', x.well_to_tank_emissions_n2o = '0'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_activities
              MODIFY well_to_tank_emission_factor_co2eq NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_co2 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_ch4 NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_n2o NUMERIC(20, 8) NOT NULL,
              MODIFY well_to_tank_emission_factor_year INT NOT NULL,
              MODIFY well_to_tank_emission_factor_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              MODIFY well_to_tank_emission_factor_unit VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2eq VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_co2 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_ch4 VARCHAR(255) NOT NULL,
              MODIFY well_to_tank_emissions_n2o VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE electricity_activities
              ADD location_based_well_to_tank_emission_factor_co2eq VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emission_factor_co2 VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emission_factor_ch4 VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emission_factor_n2o VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emission_factor_source VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emissions_co2eq VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emissions_co2 VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emissions_ch4 VARCHAR(255) DEFAULT NULL,
              ADD location_based_well_to_tank_emissions_n2o VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              combustible_activities
            DROP
              well_to_tank_emission_factor_co2eq,
            DROP
              well_to_tank_emission_factor_co2,
            DROP
              well_to_tank_emission_factor_ch4,
            DROP
              well_to_tank_emission_factor_n2o,
            DROP
              well_to_tank_emission_factor_year,
            DROP
              well_to_tank_emission_factor_emission_factor_group_id,
            DROP
              well_to_tank_emission_factor_unit,
            DROP
              well_to_tank_emission_factor_source,
            DROP
              well_to_tank_emissions_co2eq,
            DROP
              well_to_tank_emissions_co2,
            DROP
              well_to_tank_emissions_ch4,
            DROP
              well_to_tank_emissions_n2o
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              combustible_emission_factor
            DROP
              well_to_tank_co2eq,
            DROP
              well_to_tank_co2,
            DROP
              well_to_tank_ch4,
            DROP
              well_to_tank_n2o,
            DROP
              well_to_tank_year,
            DROP
              well_to_tank_emission_factor_group_id,
            DROP
              well_to_tank_unit,
            DROP
              well_to_tank_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_activities
            DROP
              location_based_well_to_tank_emission_factor_co2eq,
            DROP
              location_based_well_to_tank_emission_factor_co2,
            DROP
              location_based_well_to_tank_emission_factor_ch4,
            DROP
              location_based_well_to_tank_emission_factor_n2o,
            DROP
              location_based_well_to_tank_emission_factor_source,
            DROP
              location_based_well_to_tank_emissions_co2eq,
            DROP
              location_based_well_to_tank_emissions_co2,
            DROP
              location_based_well_to_tank_emissions_ch4,
            DROP
              location_based_well_to_tank_emissions_n2o,
            DROP
              well_to_tank_emission_factor_co2eq,
            DROP
              well_to_tank_emission_factor_co2,
            DROP
              well_to_tank_emission_factor_ch4,
            DROP
              well_to_tank_emission_factor_n2o,
            DROP
              well_to_tank_emission_factor_year,
            DROP
              well_to_tank_emission_factor_emission_factor_group_id,
            DROP
              well_to_tank_emission_factor_unit,
            DROP
              well_to_tank_emission_factor_source,
            DROP
              well_to_tank_emissions_co2eq,
            DROP
              well_to_tank_emissions_co2,
            DROP
              well_to_tank_emissions_ch4,
            DROP
              well_to_tank_emissions_n2o
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_marketer_emission_factor
            DROP
              well_to_tank_co2eq,
            DROP
              well_to_tank_co2,
            DROP
              well_to_tank_ch4,
            DROP
              well_to_tank_n2o,
            DROP
              well_to_tank_year,
            DROP
              well_to_tank_emission_factor_group_id,
            DROP
              well_to_tank_unit,
            DROP
              well_to_tank_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_mix_emission_factor
            DROP
              well_to_tank_co2eq,
            DROP
              well_to_tank_co2,
            DROP
              well_to_tank_ch4,
            DROP
              well_to_tank_n2o,
            DROP
              well_to_tank_year,
            DROP
              well_to_tank_emission_factor_group_id,
            DROP
              well_to_tank_unit,
            DROP
              well_to_tank_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              gdo_factor_emission_factor
            DROP
              well_to_tank_co2eq,
            DROP
              well_to_tank_co2,
            DROP
              well_to_tank_ch4,
            DROP
              well_to_tank_n2o,
            DROP
              well_to_tank_year,
            DROP
              well_to_tank_emission_factor_group_id,
            DROP
              well_to_tank_unit,
            DROP
              well_to_tank_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_activities
            DROP
              well_to_tank_emission_factor_co2eq,
            DROP
              well_to_tank_emission_factor_co2,
            DROP
              well_to_tank_emission_factor_ch4,
            DROP
              well_to_tank_emission_factor_n2o,
            DROP
              well_to_tank_emission_factor_year,
            DROP
              well_to_tank_emission_factor_emission_factor_group_id,
            DROP
              well_to_tank_emission_factor_unit,
            DROP
              well_to_tank_emission_factor_source,
            DROP
              well_to_tank_emissions_co2eq,
            DROP
              well_to_tank_emissions_co2,
            DROP
              well_to_tank_emissions_ch4,
            DROP
              well_to_tank_emissions_n2o
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_emission_factor
            DROP
              well_to_tank_co2eq,
            DROP
              well_to_tank_co2,
            DROP
              well_to_tank_ch4,
            DROP
              well_to_tank_n2o,
            DROP
              well_to_tank_year,
            DROP
              well_to_tank_emission_factor_group_id,
            DROP
              well_to_tank_unit,
            DROP
              well_to_tank_source
        SQL);
    }
}
