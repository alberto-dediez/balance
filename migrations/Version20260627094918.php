<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627094918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE waste_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:waste_emission_factor_id)',
              waste_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:waste_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_872F36A1FA6A22C2 (waste_id),
              UNIQUE INDEX uq_waste_emission_factor_year (waste_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE waste_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              waste_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:waste_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_7CDF8BABFA6A22C2 (waste_id),
              PRIMARY KEY(locale, waste_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE wastes (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:waste_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              density DOUBLE PRECISION NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_emission_factor
            ADD
              CONSTRAINT FK_872F36A1FA6A22C2 FOREIGN KEY (waste_id) REFERENCES wastes (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_translation
            ADD
              CONSTRAINT FK_7CDF8BABFA6A22C2 FOREIGN KEY (waste_id) REFERENCES wastes (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE waste_emission_factor DROP FOREIGN KEY FK_872F36A1FA6A22C2');
        $this->addSql('ALTER TABLE waste_translation DROP FOREIGN KEY FK_7CDF8BABFA6A22C2');
        $this->addSql('DROP TABLE waste_emission_factor');
        $this->addSql('DROP TABLE waste_translation');
        $this->addSql('DROP TABLE wastes');
    }
}
