<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630054126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_carbon_footprints (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              event_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              status VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              calculation_period_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              calculation_period_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              total_emissions_co2eq VARCHAR(255) NOT NULL,
              total_emissions_co2 VARCHAR(255) NOT NULL,
              total_emissions_ch4 VARCHAR(255) NOT NULL,
              total_emissions_n2o VARCHAR(255) NOT NULL,
              emissions_per_attendee_co2eq VARCHAR(255) NOT NULL,
              emissions_per_attendee_co2 VARCHAR(255) NOT NULL,
              emissions_per_attendee_ch4 VARCHAR(255) NOT NULL,
              emissions_per_attendee_n2o VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE event_carbon_footprints');
    }
}
