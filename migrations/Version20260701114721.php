<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701114721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              account
            ADD
              emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM activities
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM catering_item_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM combustible_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM electricity_marketer_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM gdo_factor_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM machinery_fuel_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM material_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM merchandising_item_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM mobility_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM overnight_stay_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM road_transport_emission_factor
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE
              activities
            ADD
              emission_factor_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql('DROP INDEX uq_catering_item_emission_factor_year ON catering_item_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              catering_item_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
            ADD
              recycled_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_catering_item_emission_factor_year ON catering_item_emission_factor (
              catering_item_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_combustible_emission_factor_year ON combustible_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              combustible_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_combustible_emission_factor_year ON combustible_emission_factor (
              combustible_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql(<<<'SQL'
            DROP
              INDEX uq_electricity_marketer_emission_factor_year ON electricity_marketer_emission_factor
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_marketer_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_electricity_marketer_emission_factor_year ON electricity_marketer_emission_factor (
              electricity_marketer_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_gdo_factor_emission_factor_year ON gdo_factor_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              gdo_factor_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_gdo_factor_emission_factor_year ON gdo_factor_emission_factor (
              gdo_factor_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_machinery_fuel_emission_factor_year ON machinery_fuel_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_machinery_fuel_emission_factor_year ON machinery_fuel_emission_factor (
              machinery_fuel_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_material_emission_factor_year ON material_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
            ADD
              recycled_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_material_emission_factor_year ON material_emission_factor (
              material_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_merchandising_item_emission_factor_year ON merchandising_item_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
            ADD
              recycled_emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_merchandising_item_emission_factor_year ON merchandising_item_emission_factor (
              merchandising_item_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_mobility_emission_factor_year ON mobility_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mobility_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_mobility_emission_factor_year ON mobility_emission_factor (
              mobility_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_overnight_stay_emission_factor_year ON overnight_stay_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              overnight_stay_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_overnight_stay_emission_factor_year ON overnight_stay_emission_factor (
              overnight_stay_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_road_transport_emission_factor_year ON road_transport_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_road_transport_emission_factor_year ON road_transport_emission_factor (
              road_transport_id, year, emission_factor_group_id
            )
        SQL);
        $this->addSql('DROP INDEX uq_waste_emission_factor_year ON waste_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_emission_factor
            ADD
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_waste_emission_factor_year ON waste_emission_factor (
              waste_id, year, emission_factor_group_id
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP emission_factor_group_id');
        $this->addSql('ALTER TABLE activities DROP emission_factor_emission_factor_group_id');
        $this->addSql('DROP INDEX uq_catering_item_emission_factor_year ON catering_item_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              catering_item_emission_factor
            DROP
              emission_factor_group_id,
            DROP
              recycled_emission_factor_group_id
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_catering_item_emission_factor_year ON catering_item_emission_factor (catering_item_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_combustible_emission_factor_year ON combustible_emission_factor');
        $this->addSql('ALTER TABLE combustible_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_combustible_emission_factor_year ON combustible_emission_factor (combustible_id, year)
        SQL);
        $this->addSql(<<<'SQL'
            DROP
              INDEX uq_electricity_marketer_emission_factor_year ON electricity_marketer_emission_factor
        SQL);
        $this->addSql('ALTER TABLE electricity_marketer_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_electricity_marketer_emission_factor_year ON electricity_marketer_emission_factor (electricity_marketer_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_gdo_factor_emission_factor_year ON gdo_factor_emission_factor');
        $this->addSql('ALTER TABLE gdo_factor_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_gdo_factor_emission_factor_year ON gdo_factor_emission_factor (gdo_factor_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_machinery_fuel_emission_factor_year ON machinery_fuel_emission_factor');
        $this->addSql('ALTER TABLE machinery_fuel_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_machinery_fuel_emission_factor_year ON machinery_fuel_emission_factor (machinery_fuel_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_material_emission_factor_year ON material_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_emission_factor
            DROP
              emission_factor_group_id,
            DROP
              recycled_emission_factor_group_id
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_material_emission_factor_year ON material_emission_factor (material_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_merchandising_item_emission_factor_year ON merchandising_item_emission_factor');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_emission_factor
            DROP
              emission_factor_group_id,
            DROP
              recycled_emission_factor_group_id
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_merchandising_item_emission_factor_year ON merchandising_item_emission_factor (merchandising_item_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_mobility_emission_factor_year ON mobility_emission_factor');
        $this->addSql('ALTER TABLE mobility_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_mobility_emission_factor_year ON mobility_emission_factor (mobility_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_overnight_stay_emission_factor_year ON overnight_stay_emission_factor');
        $this->addSql('ALTER TABLE overnight_stay_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_overnight_stay_emission_factor_year ON overnight_stay_emission_factor (overnight_stay_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_road_transport_emission_factor_year ON road_transport_emission_factor');
        $this->addSql('ALTER TABLE road_transport_emission_factor DROP emission_factor_group_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_road_transport_emission_factor_year ON road_transport_emission_factor (road_transport_id, year)
        SQL);
        $this->addSql('DROP INDEX uq_waste_emission_factor_year ON waste_emission_factor');
        $this->addSql('ALTER TABLE waste_emission_factor DROP emission_factor_group_id');
        $this->addSql('CREATE UNIQUE INDEX uq_waste_emission_factor_year ON waste_emission_factor (waste_id, year)');
    }
}
