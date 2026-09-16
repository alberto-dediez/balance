<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916154427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE company_carbon_footprint_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_activity_id)',
              company_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              activity_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              scope VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              added_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_E4434A9F1AA6FD85 (company_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE company_carbon_footprint_exclusions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_exclusion_id)',
              company_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              scope VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              reason VARCHAR(255) NOT NULL,
              comment LONGTEXT DEFAULT NULL,
              excluded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_ccf_exclusions_scope_source (
                company_carbon_footprint_id, scope,
                source
              ),
              INDEX IDX_EB5F66A1AA6FD85 (company_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE company_carbon_footprints (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              company_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              year INT NOT NULL,
              status VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              total_emissions_co2eq VARCHAR(255) NOT NULL,
              total_emissions_co2 VARCHAR(255) NOT NULL,
              total_emissions_ch4 VARCHAR(255) NOT NULL,
              total_emissions_n2o VARCHAR(255) NOT NULL,
              INDEX IDX_ccf_company_year (company_id, year),
              INDEX IDX_ccf_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprint_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_activity_id)',
              property_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              activity_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              scope VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              added_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_C42EA9E44DAAD22 (property_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprint_exclusions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_exclusion_id)',
              property_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              scope VARCHAR(255) NOT NULL,
              source VARCHAR(255) NOT NULL,
              reason VARCHAR(255) NOT NULL,
              comment LONGTEXT DEFAULT NULL,
              excluded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_pcf_exclusions_scope_source (
                property_carbon_footprint_id, scope,
                source
              ),
              INDEX IDX_2ED815114DAAD22 (property_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprints (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              year INT NOT NULL,
              status VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              total_emissions_co2eq VARCHAR(255) NOT NULL,
              total_emissions_co2 VARCHAR(255) NOT NULL,
              total_emissions_ch4 VARCHAR(255) NOT NULL,
              total_emissions_n2o VARCHAR(255) NOT NULL,
              INDEX IDX_pcf_property_year (property_id, year),
              INDEX IDX_pcf_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_activities
            ADD
              CONSTRAINT FK_E4434A9F1AA6FD85 FOREIGN KEY (company_carbon_footprint_id) REFERENCES company_carbon_footprints (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_exclusions
            ADD
              CONSTRAINT FK_EB5F66A1AA6FD85 FOREIGN KEY (company_carbon_footprint_id) REFERENCES company_carbon_footprints (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_activities
            ADD
              CONSTRAINT FK_C42EA9E44DAAD22 FOREIGN KEY (property_carbon_footprint_id) REFERENCES property_carbon_footprints (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_exclusions
            ADD
              CONSTRAINT FK_2ED815114DAAD22 FOREIGN KEY (property_carbon_footprint_id) REFERENCES property_carbon_footprints (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company_carbon_footprint_activities DROP FOREIGN KEY FK_E4434A9F1AA6FD85');
        $this->addSql('ALTER TABLE company_carbon_footprint_exclusions DROP FOREIGN KEY FK_EB5F66A1AA6FD85');
        $this->addSql('ALTER TABLE property_carbon_footprint_activities DROP FOREIGN KEY FK_C42EA9E44DAAD22');
        $this->addSql('ALTER TABLE property_carbon_footprint_exclusions DROP FOREIGN KEY FK_2ED815114DAAD22');
        $this->addSql('DROP TABLE company_carbon_footprint_activities');
        $this->addSql('DROP TABLE company_carbon_footprint_exclusions');
        $this->addSql('DROP TABLE company_carbon_footprints');
        $this->addSql('DROP TABLE property_carbon_footprint_activities');
        $this->addSql('DROP TABLE property_carbon_footprint_exclusions');
        $this->addSql('DROP TABLE property_carbon_footprints');
    }
}
