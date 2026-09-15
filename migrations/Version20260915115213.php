<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915115213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE electricity_mix_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:electricity_mix_emission_factor_id)',
              electricity_mix_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:electricity_mix_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX uq_electricity_mix_emission_factor_year (
                electricity_mix_id, year, emission_factor_group_id
              ),
              INDEX IDX_FC4C3BD936D646F0 (electricity_mix_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE electricity_mixes (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:electricity_mix_id)',
              country_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:country_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_electricity_mixes_country (country_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_mix_emission_factor
            ADD
              CONSTRAINT FK_FC4C3BD936D646F0 FOREIGN KEY (electricity_mix_id) REFERENCES electricity_mixes (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_activities
            ADD
              electricity_mix_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:electricity_mix_id)',
            ADD
              location_based_emission_factor_co2eq VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emission_factor_co2 VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emission_factor_ch4 VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emission_factor_n2o VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emission_factor_source VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emissions_co2eq VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emissions_co2 VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emissions_ch4 VARCHAR(255) DEFAULT NULL,
            ADD
              location_based_emissions_n2o VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE electricity_mix_emission_factor DROP FOREIGN KEY FK_FC4C3BD936D646F0');
        $this->addSql('DROP TABLE electricity_mix_emission_factor');
        $this->addSql('DROP TABLE electricity_mixes');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_activities
            DROP
              electricity_mix_id,
            DROP
              location_based_emission_factor_co2eq,
            DROP
              location_based_emission_factor_co2,
            DROP
              location_based_emission_factor_ch4,
            DROP
              location_based_emission_factor_n2o,
            DROP
              location_based_emission_factor_source,
            DROP
              location_based_emissions_co2eq,
            DROP
              location_based_emissions_co2,
            DROP
              location_based_emissions_ch4,
            DROP
              location_based_emissions_n2o
        SQL);
    }
}
