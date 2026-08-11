<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811145213 extends AbstractMigration
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
              attendee_survey_responses
            ADD
              emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
            ADD
              calculation_year INT DEFAULT NULL,
            ADD
              emissions_co2eq VARCHAR(255) NOT NULL,
            ADD
              emissions_co2 VARCHAR(255) NOT NULL,
            ADD
              emissions_ch4 VARCHAR(255) NOT NULL,
            ADD
              emissions_n2o VARCHAR(255) NOT NULL,
            ADD
              mobility_emissions_co2eq VARCHAR(255) NOT NULL,
            ADD
              mobility_emissions_co2 VARCHAR(255) NOT NULL,
            ADD
              mobility_emissions_ch4 VARCHAR(255) NOT NULL,
            ADD
              mobility_emissions_n2o VARCHAR(255) NOT NULL,
            ADD
              overnight_emissions_co2eq VARCHAR(255) NOT NULL,
            ADD
              overnight_emissions_co2 VARCHAR(255) NOT NULL,
            ADD
              overnight_emissions_ch4 VARCHAR(255) NOT NULL,
            ADD
              overnight_emissions_n2o VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            DROP
              emission_factor_group_id,
            DROP
              calculation_year,
            DROP
              emissions_co2eq,
            DROP
              emissions_co2,
            DROP
              emissions_ch4,
            DROP
              emissions_n2o,
            DROP
              mobility_emissions_co2eq,
            DROP
              mobility_emissions_co2,
            DROP
              mobility_emissions_ch4,
            DROP
              mobility_emissions_n2o,
            DROP
              overnight_emissions_co2eq,
            DROP
              overnight_emissions_co2,
            DROP
              overnight_emissions_ch4,
            DROP
              overnight_emissions_n2o
        SQL);
    }
}
