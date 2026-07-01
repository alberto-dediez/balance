<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701092943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              emission_factor_co2eq NUMERIC(20, 8) NOT NULL,
              emission_factor_co2 NUMERIC(20, 8) NOT NULL,
              emission_factor_ch4 NUMERIC(20, 8) NOT NULL,
              emission_factor_n2o NUMERIC(20, 8) NOT NULL,
              emission_factor_year INT NOT NULL,
              emission_factor_unit VARCHAR(255) NOT NULL,
              emission_factor_source VARCHAR(255) DEFAULT NULL,
              emissions_co2eq VARCHAR(255) NOT NULL,
              emissions_co2 VARCHAR(255) NOT NULL,
              emissions_ch4 VARCHAR(255) NOT NULL,
              emissions_n2o VARCHAR(255) NOT NULL,
              value DOUBLE PRECISION NOT NULL,
              unit VARCHAR(255) NOT NULL,
              type VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE catering_item_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              catering_item_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:catering_item_id)',
              `condition` VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE combustible_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              combustible_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:combustible_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE electricity_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              electricity_marketer_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:electricity_marketer_id)',
              gdo_factor_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_carbon_footprint_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_activity_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              activity_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              phase VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              added_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_3EA0A08BB6931EEA (event_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE machinery_fuel_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              machinery_fuel_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:machinery_fuel_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE material_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              material_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:material_id)',
              `condition` VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE merchandising_item_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              merchandising_item_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:merchandising_item_id)',
              `condition` VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mobility_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              mobility_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mobility_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE overnight_stay_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              overnight_stay_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:overnight_stay_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE road_transport_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE waste_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              waste_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:waste_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              catering_item_activities
            ADD
              CONSTRAINT FK_6276EF6DBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              combustible_activities
            ADD
              CONSTRAINT FK_AB4030DCBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_activities
            ADD
              CONSTRAINT FK_A02E2C01BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_carbon_footprint_activities
            ADD
              CONSTRAINT FK_3EA0A08BB6931EEA FOREIGN KEY (event_carbon_footprint_id) REFERENCES event_carbon_footprints (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_activities
            ADD
              CONSTRAINT FK_FBC1F61BBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_activities
            ADD
              CONSTRAINT FK_15C3D387BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_activities
            ADD
              CONSTRAINT FK_FB7326F4BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mobility_activities
            ADD
              CONSTRAINT FK_8F2B34ACBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              overnight_stay_activities
            ADD
              CONSTRAINT FK_9A9D63C0BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_activities
            ADD
              CONSTRAINT FK_BDF165F6BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_activities
            ADD
              CONSTRAINT FK_D46EEBD7BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_item_activities DROP FOREIGN KEY FK_6276EF6DBF396750');
        $this->addSql('ALTER TABLE combustible_activities DROP FOREIGN KEY FK_AB4030DCBF396750');
        $this->addSql('ALTER TABLE electricity_activities DROP FOREIGN KEY FK_A02E2C01BF396750');
        $this->addSql('ALTER TABLE event_carbon_footprint_activities DROP FOREIGN KEY FK_3EA0A08BB6931EEA');
        $this->addSql('ALTER TABLE machinery_fuel_activities DROP FOREIGN KEY FK_FBC1F61BBF396750');
        $this->addSql('ALTER TABLE material_activities DROP FOREIGN KEY FK_15C3D387BF396750');
        $this->addSql('ALTER TABLE merchandising_item_activities DROP FOREIGN KEY FK_FB7326F4BF396750');
        $this->addSql('ALTER TABLE mobility_activities DROP FOREIGN KEY FK_8F2B34ACBF396750');
        $this->addSql('ALTER TABLE overnight_stay_activities DROP FOREIGN KEY FK_9A9D63C0BF396750');
        $this->addSql('ALTER TABLE road_transport_activities DROP FOREIGN KEY FK_BDF165F6BF396750');
        $this->addSql('ALTER TABLE waste_activities DROP FOREIGN KEY FK_D46EEBD7BF396750');
        $this->addSql('DROP TABLE activities');
        $this->addSql('DROP TABLE catering_item_activities');
        $this->addSql('DROP TABLE combustible_activities');
        $this->addSql('DROP TABLE electricity_activities');
        $this->addSql('DROP TABLE event_carbon_footprint_activities');
        $this->addSql('DROP TABLE machinery_fuel_activities');
        $this->addSql('DROP TABLE material_activities');
        $this->addSql('DROP TABLE merchandising_item_activities');
        $this->addSql('DROP TABLE mobility_activities');
        $this->addSql('DROP TABLE overnight_stay_activities');
        $this->addSql('DROP TABLE road_transport_activities');
        $this->addSql('DROP TABLE waste_activities');
    }
}
