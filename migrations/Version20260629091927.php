<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629091927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE overnight_stay_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:overnight_stay_emission_factor_id)',
              overnight_stay_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:overnight_stay_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_A47A4CE537E9DF48 (overnight_stay_id),
              UNIQUE INDEX uq_overnight_stay_emission_factor_year (overnight_stay_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE overnight_stay_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              overnight_stay_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:overnight_stay_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_FF42FDE437E9DF48 (overnight_stay_id),
              PRIMARY KEY(locale, overnight_stay_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE overnight_stays (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:overnight_stay_id)',
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
              overnight_stay_emission_factor
            ADD
              CONSTRAINT FK_A47A4CE537E9DF48 FOREIGN KEY (overnight_stay_id) REFERENCES overnight_stays (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              overnight_stay_translation
            ADD
              CONSTRAINT FK_FF42FDE437E9DF48 FOREIGN KEY (overnight_stay_id) REFERENCES overnight_stays (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overnight_stay_emission_factor DROP FOREIGN KEY FK_A47A4CE537E9DF48');
        $this->addSql('ALTER TABLE overnight_stay_translation DROP FOREIGN KEY FK_FF42FDE437E9DF48');
        $this->addSql('DROP TABLE overnight_stay_emission_factor');
        $this->addSql('DROP TABLE overnight_stay_translation');
        $this->addSql('DROP TABLE overnight_stays');
    }
}
