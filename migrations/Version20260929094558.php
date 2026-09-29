<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929094558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_action_effect (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_action_effect_id)',
              mitigation_action_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_action_id)',
              source VARCHAR(255) NOT NULL,
              item_id VARCHAR(36) NOT NULL,
              item_name VARCHAR(255) NOT NULL,
              reduction DOUBLE PRECISION NOT NULL,
              minimum_reduction DOUBLE PRECISION NOT NULL,
              maximum_reduction DOUBLE PRECISION NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uq_mitigation_action_effect_source_item (
                mitigation_action_id, source, item_id
              ),
              INDEX IDX_366CDDBDD448A49 (mitigation_action_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_action_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              mitigation_action_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_action_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              INDEX IDX_645C9E4FDD448A49 (mitigation_action_id),
              PRIMARY KEY(locale, mitigation_action_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_actions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_action_id)',
              subject_type VARCHAR(20) NOT NULL,
              carbon_footprint_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              measure_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mitigation_measure_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              base_year INT NOT NULL,
              target_year INT NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_mitigation_actions_subject (
                subject_type, carbon_footprint_id
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_measure_categories (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_category_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_measure_category_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              mitigation_measure_category_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_category_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_F6E36FC03A97A3BF (mitigation_measure_category_id),
              PRIMARY KEY(
                locale, mitigation_measure_category_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_measure_effect (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_effect_id)',
              mitigation_measure_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_id)',
              source VARCHAR(255) NOT NULL,
              minimum_reduction DOUBLE PRECISION NOT NULL,
              maximum_reduction DOUBLE PRECISION NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uq_mitigation_measure_effect_source (mitigation_measure_id, source),
              INDEX IDX_31D34FE3450366D (mitigation_measure_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_measure_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              mitigation_measure_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              INDEX IDX_9CB87B32450366D (mitigation_measure_id),
              PRIMARY KEY(locale, mitigation_measure_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mitigation_measures (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mitigation_measure_id)',
              category_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mitigation_measure_category_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_mitigation_measures_category (category_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mitigation_action_effect
            ADD
              CONSTRAINT FK_366CDDBDD448A49 FOREIGN KEY (mitigation_action_id) REFERENCES mitigation_actions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mitigation_action_translation
            ADD
              CONSTRAINT FK_645C9E4FDD448A49 FOREIGN KEY (mitigation_action_id) REFERENCES mitigation_actions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mitigation_measure_category_translation
            ADD
              CONSTRAINT FK_F6E36FC03A97A3BF FOREIGN KEY (mitigation_measure_category_id) REFERENCES mitigation_measure_categories (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mitigation_measure_effect
            ADD
              CONSTRAINT FK_31D34FE3450366D FOREIGN KEY (mitigation_measure_id) REFERENCES mitigation_measures (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              mitigation_measure_translation
            ADD
              CONSTRAINT FK_9CB87B32450366D FOREIGN KEY (mitigation_measure_id) REFERENCES mitigation_measures (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprints
            ADD
              mitigation_plan_started TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprints
            ADD
              mitigation_plan_started TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mitigation_action_effect DROP FOREIGN KEY FK_366CDDBDD448A49');
        $this->addSql('ALTER TABLE mitigation_action_translation DROP FOREIGN KEY FK_645C9E4FDD448A49');
        $this->addSql('ALTER TABLE mitigation_measure_category_translation DROP FOREIGN KEY FK_F6E36FC03A97A3BF');
        $this->addSql('ALTER TABLE mitigation_measure_effect DROP FOREIGN KEY FK_31D34FE3450366D');
        $this->addSql('ALTER TABLE mitigation_measure_translation DROP FOREIGN KEY FK_9CB87B32450366D');
        $this->addSql('DROP TABLE mitigation_action_effect');
        $this->addSql('DROP TABLE mitigation_action_translation');
        $this->addSql('DROP TABLE mitigation_actions');
        $this->addSql('DROP TABLE mitigation_measure_categories');
        $this->addSql('DROP TABLE mitigation_measure_category_translation');
        $this->addSql('DROP TABLE mitigation_measure_effect');
        $this->addSql('DROP TABLE mitigation_measure_translation');
        $this->addSql('DROP TABLE mitigation_measures');
        $this->addSql('ALTER TABLE company_carbon_footprints DROP mitigation_plan_started');
        $this->addSql('ALTER TABLE property_carbon_footprints DROP mitigation_plan_started');
    }
}
