<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015124557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE capital_good_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              capital_good_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:capital_good_id)',
              acquisition_year INT DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE capital_good_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:capital_good_emission_factor_id)',
              capital_good_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:capital_good_id)',
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
              UNIQUE INDEX uq_capital_good_emission_factor_year (
                capital_good_id, year, emission_factor_group_id
              ),
              INDEX IDX_6815BB7A5D57E6D4 (capital_good_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE capital_good_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              capital_good_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:capital_good_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_90441C595D57E6D4 (capital_good_id),
              PRIMARY KEY(locale, capital_good_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE capital_goods (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:capital_good_id)',
              name VARCHAR(255) NOT NULL,
              category VARCHAR(255) NOT NULL,
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
              capital_good_activities
            ADD
              CONSTRAINT FK_2C0B3F16BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              capital_good_emission_factor
            ADD
              CONSTRAINT FK_6815BB7A5D57E6D4 FOREIGN KEY (capital_good_id) REFERENCES capital_goods (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              capital_good_translation
            ADD
              CONSTRAINT FK_90441C595D57E6D4 FOREIGN KEY (capital_good_id) REFERENCES capital_goods (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE capital_good_activities DROP FOREIGN KEY FK_2C0B3F16BF396750');
        $this->addSql('ALTER TABLE capital_good_emission_factor DROP FOREIGN KEY FK_6815BB7A5D57E6D4');
        $this->addSql('ALTER TABLE capital_good_translation DROP FOREIGN KEY FK_90441C595D57E6D4');
        $this->addSql('DROP TABLE capital_good_activities');
        $this->addSql('DROP TABLE capital_good_emission_factor');
        $this->addSql('DROP TABLE capital_good_translation');
        $this->addSql('DROP TABLE capital_goods');
    }
}
