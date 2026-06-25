<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625171935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE merchandising_item_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:merchandising_item_emission_factor_id)',
              merchandising_item_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:merchandising_item_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_838B5DE0B2A777CF (merchandising_item_id),
              UNIQUE INDEX uq_merchandising_item_emission_factor_year (merchandising_item_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE merchandising_item_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              merchandising_item_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:merchandising_item_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_DE97E714B2A777CF (merchandising_item_id),
              PRIMARY KEY(locale, merchandising_item_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE merchandising_items (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:merchandising_item_id)',
              name VARCHAR(255) NOT NULL,
              category VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              unit_weight NUMERIC(20, 8) DEFAULT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_emission_factor
            ADD
              CONSTRAINT FK_838B5DE0B2A777CF FOREIGN KEY (merchandising_item_id) REFERENCES merchandising_items (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_translation
            ADD
              CONSTRAINT FK_DE97E714B2A777CF FOREIGN KEY (merchandising_item_id) REFERENCES merchandising_items (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE merchandising_item_emission_factor DROP FOREIGN KEY FK_838B5DE0B2A777CF');
        $this->addSql('ALTER TABLE merchandising_item_translation DROP FOREIGN KEY FK_DE97E714B2A777CF');
        $this->addSql('DROP TABLE merchandising_item_emission_factor');
        $this->addSql('DROP TABLE merchandising_item_translation');
        $this->addSql('DROP TABLE merchandising_items');
    }
}
