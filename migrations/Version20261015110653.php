<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015110653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE purchase_service_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              purchase_service_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:purchase_service_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE purchase_service_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:purchase_service_emission_factor_id)',
              purchase_service_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:purchase_service_id)',
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
              UNIQUE INDEX uq_purchase_service_emission_factor_year (
                purchase_service_id, year, emission_factor_group_id
              ),
              INDEX IDX_24FF6B68E16FFE2C (purchase_service_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE purchase_service_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              purchase_service_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:purchase_service_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_E4B06F7EE16FFE2C (purchase_service_id),
              PRIMARY KEY(locale, purchase_service_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE purchase_services (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:purchase_service_id)',
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
              purchase_service_activities
            ADD
              CONSTRAINT FK_69AA8295BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              purchase_service_emission_factor
            ADD
              CONSTRAINT FK_24FF6B68E16FFE2C FOREIGN KEY (purchase_service_id) REFERENCES purchase_services (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              purchase_service_translation
            ADD
              CONSTRAINT FK_E4B06F7EE16FFE2C FOREIGN KEY (purchase_service_id) REFERENCES purchase_services (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase_service_activities DROP FOREIGN KEY FK_69AA8295BF396750');
        $this->addSql('ALTER TABLE purchase_service_emission_factor DROP FOREIGN KEY FK_24FF6B68E16FFE2C');
        $this->addSql('ALTER TABLE purchase_service_translation DROP FOREIGN KEY FK_E4B06F7EE16FFE2C');
        $this->addSql('DROP TABLE purchase_service_activities');
        $this->addSql('DROP TABLE purchase_service_emission_factor');
        $this->addSql('DROP TABLE purchase_service_translation');
        $this->addSql('DROP TABLE purchase_services');
    }
}
