<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629072120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE gdo_factor_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_emission_factor_id)',
              gdo_factor_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_AAACFEA98612A7C5 (gdo_factor_id),
              UNIQUE INDEX uq_gdo_factor_emission_factor_year (gdo_factor_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE gdo_factor_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              gdo_factor_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_A0023E2A8612A7C5 (gdo_factor_id),
              PRIMARY KEY(locale, gdo_factor_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE gdo_factors (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              gdo_factor_emission_factor
            ADD
              CONSTRAINT FK_AAACFEA98612A7C5 FOREIGN KEY (gdo_factor_id) REFERENCES gdo_factors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              gdo_factor_translation
            ADD
              CONSTRAINT FK_A0023E2A8612A7C5 FOREIGN KEY (gdo_factor_id) REFERENCES gdo_factors (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gdo_factor_emission_factor DROP FOREIGN KEY FK_AAACFEA98612A7C5');
        $this->addSql('ALTER TABLE gdo_factor_translation DROP FOREIGN KEY FK_A0023E2A8612A7C5');
        $this->addSql('DROP TABLE gdo_factor_emission_factor');
        $this->addSql('DROP TABLE gdo_factor_translation');
        $this->addSql('DROP TABLE gdo_factors');
    }
}
