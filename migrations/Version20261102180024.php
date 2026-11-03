<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102180024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE company_carbon_footprint_materiality_decisions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_materiality_decision_id)',
              company_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              topic_id VARCHAR(36) NOT NULL,
              topic_number SMALLINT DEFAULT NULL,
              source VARCHAR(255) DEFAULT NULL,
              score_percent NUMERIC(5, 2) NOT NULL,
              threshold_percent SMALLINT NOT NULL,
              forced_by_high_magnitude TINYINT(1) NOT NULL,
              verdict VARCHAR(16) NOT NULL,
              recommended_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              overridden_by_user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              overridden_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              override_justification LONGTEXT DEFAULT NULL,
              INDEX IDX_ccf_materiality_footprint (company_carbon_footprint_id),
              UNIQUE INDEX UNIQ_ccf_materiality_topic (
                company_carbon_footprint_id, topic_id
              ),
              UNIQUE INDEX UNIQ_ccf_materiality_source (
                company_carbon_footprint_id, source
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_settings (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_settings_id)',
              relevance_threshold_percent SMALLINT NOT NULL,
              high_magnitude_forces_inclusion TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprint_materiality_decisions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_materiality_decision_id)',
              property_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              topic_id VARCHAR(36) NOT NULL,
              topic_number SMALLINT DEFAULT NULL,
              source VARCHAR(255) DEFAULT NULL,
              score_percent NUMERIC(5, 2) NOT NULL,
              threshold_percent SMALLINT NOT NULL,
              forced_by_high_magnitude TINYINT(1) NOT NULL,
              verdict VARCHAR(16) NOT NULL,
              recommended_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              overridden_by_user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              overridden_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              override_justification LONGTEXT DEFAULT NULL,
              INDEX IDX_pcf_materiality_footprint (property_carbon_footprint_id),
              UNIQUE INDEX UNIQ_pcf_materiality_topic (
                property_carbon_footprint_id, topic_id
              ),
              UNIQUE INDEX UNIQ_pcf_materiality_source (
                property_carbon_footprint_id, source
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_materiality_decisions
            ADD
              CONSTRAINT FK_8FD1021D1AA6FD85 FOREIGN KEY (company_carbon_footprint_id) REFERENCES company_carbon_footprints (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_materiality_decisions
            ADD
              CONSTRAINT FK_DA5D657D4DAAD22 FOREIGN KEY (property_carbon_footprint_id) REFERENCES property_carbon_footprints (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_assessment_topics
            ADD
              threshold_percent SMALLINT DEFAULT NULL,
            ADD
              forced_by_high_magnitude TINYINT(1) DEFAULT NULL,
            ADD
              verdict VARCHAR(16) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_materiality_decisions
            DROP
              FOREIGN KEY FK_8FD1021D1AA6FD85
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_materiality_decisions
            DROP
              FOREIGN KEY FK_DA5D657D4DAAD22
        SQL);
        $this->addSql('DROP TABLE company_carbon_footprint_materiality_decisions');
        $this->addSql('DROP TABLE materiality_settings');
        $this->addSql('DROP TABLE property_carbon_footprint_materiality_decisions');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_assessment_topics
            DROP
              threshold_percent,
            DROP
              forced_by_high_magnitude,
            DROP
              verdict
        SQL);
    }
}
