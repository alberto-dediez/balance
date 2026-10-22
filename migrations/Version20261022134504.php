<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261022134504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE company_carbon_footprint_non_consolidated_properties (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_non_consolidated_property_id)',
              company_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              source VARCHAR(255) NOT NULL,
              property_carbon_footprint_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              manual_co2eq VARCHAR(255) DEFAULT NULL,
              recorded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              calculated_emissions_co2eq VARCHAR(255) NOT NULL,
              calculated_emissions_co2 VARCHAR(255) NOT NULL,
              calculated_emissions_ch4 VARCHAR(255) NOT NULL,
              calculated_emissions_n2o VARCHAR(255) NOT NULL,
              UNIQUE INDEX UNIQ_ccf_ncp_property (
                company_carbon_footprint_id, property_id
              ),
              INDEX IDX_3F2FD39E1AA6FD85 (company_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_non_consolidated_properties
            ADD
              CONSTRAINT FK_3F2FD39E1AA6FD85 FOREIGN KEY (company_carbon_footprint_id) REFERENCES company_carbon_footprints (id)
        SQL);
        $this->addSql('ALTER TABLE properties ADD operating_regime VARCHAR(255) DEFAULT \'owned\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_non_consolidated_properties
            DROP
              FOREIGN KEY FK_3F2FD39E1AA6FD85
        SQL);
        $this->addSql('DROP TABLE company_carbon_footprint_non_consolidated_properties');
        $this->addSql('ALTER TABLE properties DROP operating_regime');
    }
}
