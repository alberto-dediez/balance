<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261016085141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE economic_sector_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              economic_sector_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:economic_sector_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE economic_sector_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:economic_sector_emission_factor_id)',
              economic_sector_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:economic_sector_id)',
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
              UNIQUE INDEX uq_economic_sector_emission_factor_year (
                economic_sector_id, year, emission_factor_group_id
              ),
              INDEX IDX_34EA9D74C36BFB08 (economic_sector_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE economic_sector_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              economic_sector_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:economic_sector_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_CD66329BC36BFB08 (economic_sector_id),
              PRIMARY KEY(locale, economic_sector_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE economic_sectors (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:economic_sector_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              classification_code VARCHAR(20) DEFAULT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_economic_sector_classification_code (classification_code),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              economic_sector_activities
            ADD
              CONSTRAINT FK_663EFFECBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              economic_sector_emission_factor
            ADD
              CONSTRAINT FK_34EA9D74C36BFB08 FOREIGN KEY (economic_sector_id) REFERENCES economic_sectors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              economic_sector_translation
            ADD
              CONSTRAINT FK_CD66329BC36BFB08 FOREIGN KEY (economic_sector_id) REFERENCES economic_sectors (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE economic_sector_activities DROP FOREIGN KEY FK_663EFFECBF396750');
        $this->addSql('ALTER TABLE economic_sector_emission_factor DROP FOREIGN KEY FK_34EA9D74C36BFB08');
        $this->addSql('ALTER TABLE economic_sector_translation DROP FOREIGN KEY FK_CD66329BC36BFB08');
        $this->addSql('DROP TABLE economic_sector_activities');
        $this->addSql('DROP TABLE economic_sector_emission_factor');
        $this->addSql('DROP TABLE economic_sector_translation');
        $this->addSql('DROP TABLE economic_sectors');
    }
}
