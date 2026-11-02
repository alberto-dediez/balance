<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102101743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE carbon_footprint_base_year_declarations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:base_year_declaration_id)',
              subject_type VARCHAR(16) NOT NULL,
              subject_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              year INT NOT NULL,
              justification LONGTEXT NOT NULL,
              recalculation_threshold_percent NUMERIC(5, 2) NOT NULL,
              primary_indicator VARCHAR(32) DEFAULT NULL,
              base_year_total_kg_co2eq NUMERIC(16, 2) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_byd_account (account_id),
              UNIQUE INDEX UNIQ_byd_subject (subject_type, subject_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE carbon_footprint_sector_benchmarks (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:sector_benchmark_id)',
              subject_type VARCHAR(16) NOT NULL,
              segment_key VARCHAR(32) NOT NULL,
              year INT NOT NULL,
              indicator VARCHAR(32) NOT NULL,
              sample_size INT NOT NULL,
              account_count INT NOT NULL,
              median NUMERIC(16, 4) NOT NULL,
              percentile_25 NUMERIC(16, 4) NOT NULL,
              percentile_75 NUMERIC(16, 4) NOT NULL,
              mean NUMERIC(16, 4) NOT NULL,
              computed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_sb_segment (
                subject_type, segment_key, year, indicator
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprints
            ADD
              denominator_occupied_rooms INT DEFAULT NULL,
            ADD
              denominator_stays INT DEFAULT NULL,
            ADD
              denominator_built_area_m2 NUMERIC(12, 2) DEFAULT NULL,
            ADD
              denominator_covers_served INT DEFAULT NULL,
            ADD
              denominator_average_headcount NUMERIC(10, 2) DEFAULT NULL,
            ADD
              denominator_revenue_eur NUMERIC(16, 2) DEFAULT NULL,
            ADD
              denominator_customers_served INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprints
            ADD
              denominator_occupied_rooms INT DEFAULT NULL,
            ADD
              denominator_stays INT DEFAULT NULL,
            ADD
              denominator_built_area_m2 NUMERIC(12, 2) DEFAULT NULL,
            ADD
              denominator_covers_served INT DEFAULT NULL,
            ADD
              denominator_average_headcount NUMERIC(10, 2) DEFAULT NULL,
            ADD
              denominator_revenue_eur NUMERIC(16, 2) DEFAULT NULL,
            ADD
              denominator_customers_served INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE carbon_footprint_base_year_declarations');
        $this->addSql('DROP TABLE carbon_footprint_sector_benchmarks');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprints
            DROP
              denominator_occupied_rooms,
            DROP
              denominator_stays,
            DROP
              denominator_built_area_m2,
            DROP
              denominator_covers_served,
            DROP
              denominator_average_headcount,
            DROP
              denominator_revenue_eur,
            DROP
              denominator_customers_served
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprints
            DROP
              denominator_occupied_rooms,
            DROP
              denominator_stays,
            DROP
              denominator_built_area_m2,
            DROP
              denominator_covers_served,
            DROP
              denominator_average_headcount,
            DROP
              denominator_revenue_eur,
            DROP
              denominator_customers_served
        SQL);
    }
}
