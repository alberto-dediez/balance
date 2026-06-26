<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626121128 extends AbstractMigration
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
              catering_item_emission_factor
            ADD
              recycled_co2eq NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_co2 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_ch4 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_n2o NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_year INT NOT NULL,
            ADD
              recycled_unit VARCHAR(255) NOT NULL,
            ADD
              recycled_source VARCHAR(255) DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE
              catering_item_emission_factor
            SET
              recycled_co2eq = co2eq,
              recycled_co2 = co2,
              recycled_ch4 = ch4,
              recycled_n2o = n2o,
              recycled_year = year,
              recycled_unit = unit,
              recycled_source = source
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_emission_factor
            ADD
              recycled_co2eq NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_co2 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_ch4 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_n2o NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_year INT NOT NULL,
            ADD
              recycled_unit VARCHAR(255) NOT NULL,
            ADD
              recycled_source VARCHAR(255) DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE
              material_emission_factor
            SET
              recycled_co2eq = co2eq,
              recycled_co2 = co2,
              recycled_ch4 = ch4,
              recycled_n2o = n2o,
              recycled_year = year,
              recycled_unit = unit,
              recycled_source = source
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_emission_factor
            ADD
              recycled_co2eq NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_co2 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_ch4 NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_n2o NUMERIC(20, 8) NOT NULL,
            ADD
              recycled_year INT NOT NULL,
            ADD
              recycled_unit VARCHAR(255) NOT NULL,
            ADD
              recycled_source VARCHAR(255) DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE
              merchandising_item_emission_factor
            SET
              recycled_co2eq = co2eq,
              recycled_co2 = co2,
              recycled_ch4 = ch4,
              recycled_n2o = n2o,
              recycled_year = year,
              recycled_unit = unit,
              recycled_source = source
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              catering_item_emission_factor
            DROP
              recycled_co2eq,
            DROP
              recycled_co2,
            DROP
              recycled_ch4,
            DROP
              recycled_n2o,
            DROP
              recycled_year,
            DROP
              recycled_unit,
            DROP
              recycled_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_emission_factor
            DROP
              recycled_co2eq,
            DROP
              recycled_co2,
            DROP
              recycled_ch4,
            DROP
              recycled_n2o,
            DROP
              recycled_year,
            DROP
              recycled_unit,
            DROP
              recycled_source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_emission_factor
            DROP
              recycled_co2eq,
            DROP
              recycled_co2,
            DROP
              recycled_ch4,
            DROP
              recycled_n2o,
            DROP
              recycled_year,
            DROP
              recycled_unit,
            DROP
              recycled_source
        SQL);
    }
}
