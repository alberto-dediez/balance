<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626183238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE machinery_fuel_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:machinery_fuel_emission_factor_id)',
              machinery_fuel_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:machinery_fuel_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_C9819E0992CD7AA0 (machinery_fuel_id),
              UNIQUE INDEX uq_machinery_fuel_emission_factor_year (machinery_fuel_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE machinery_fuel_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              machinery_fuel_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:machinery_fuel_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_EE22AA2D92CD7AA0 (machinery_fuel_id),
              PRIMARY KEY(locale, machinery_fuel_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE machinery_fuels (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:machinery_fuel_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              machinery_type VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_emission_factor
            ADD
              CONSTRAINT FK_C9819E0992CD7AA0 FOREIGN KEY (machinery_fuel_id) REFERENCES machinery_fuels (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              machinery_fuel_translation
            ADD
              CONSTRAINT FK_EE22AA2D92CD7AA0 FOREIGN KEY (machinery_fuel_id) REFERENCES machinery_fuels (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE machinery_fuel_emission_factor DROP FOREIGN KEY FK_C9819E0992CD7AA0');
        $this->addSql('ALTER TABLE machinery_fuel_translation DROP FOREIGN KEY FK_EE22AA2D92CD7AA0');
        $this->addSql('DROP TABLE machinery_fuel_emission_factor');
        $this->addSql('DROP TABLE machinery_fuel_translation');
        $this->addSql('DROP TABLE machinery_fuels');
    }
}
