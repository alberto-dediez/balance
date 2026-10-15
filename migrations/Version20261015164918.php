<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015164918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE process_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              process_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:process_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE process_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:process_emission_factor_id)',
              process_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:process_id)',
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
              UNIQUE INDEX uq_process_emission_factor_year (
                process_id, year, emission_factor_group_id
              ),
              INDEX IDX_CAD0F517EC2F574 (process_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE process_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              process_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:process_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_BA1C867E7EC2F574 (process_id),
              PRIMARY KEY(locale, process_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE processes (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:process_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              process_activities
            ADD
              CONSTRAINT FK_1BBA8CEDBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              process_emission_factor
            ADD
              CONSTRAINT FK_CAD0F517EC2F574 FOREIGN KEY (process_id) REFERENCES processes (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              process_translation
            ADD
              CONSTRAINT FK_BA1C867E7EC2F574 FOREIGN KEY (process_id) REFERENCES processes (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE process_activities DROP FOREIGN KEY FK_1BBA8CEDBF396750');
        $this->addSql('ALTER TABLE process_emission_factor DROP FOREIGN KEY FK_CAD0F517EC2F574');
        $this->addSql('ALTER TABLE process_translation DROP FOREIGN KEY FK_BA1C867E7EC2F574');
        $this->addSql('DROP TABLE process_activities');
        $this->addSql('DROP TABLE process_emission_factor');
        $this->addSql('DROP TABLE process_translation');
        $this->addSql('DROP TABLE processes');
    }
}
