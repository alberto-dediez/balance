<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261027181825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_provider_contribution_sources (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_provider_contribution_source_id)',
              contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_provider_contribution_id)',
              source VARCHAR(50) NOT NULL,
              UNIQUE INDEX UNIQ_opcs_contribution_source (contribution_id, source),
              INDEX IDX_EC2F3F3EFE5E5FBD (contribution_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organizational_provider_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:organizational_provider_contribution_id)',
              subject_type VARCHAR(20) NOT NULL,
              carbon_footprint_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              provider_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:provider_id)',
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              mode VARCHAR(20) NOT NULL,
              token VARCHAR(64) DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ignored_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              company_name VARCHAR(255) DEFAULT NULL,
              service VARCHAR(255) DEFAULT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              consent_policy_version VARCHAR(64) DEFAULT NULL,
              consent_locale VARCHAR(5) DEFAULT NULL,
              consent_ip VARCHAR(45) DEFAULT NULL,
              consent_user_agent VARCHAR(512) DEFAULT NULL,
              consent_accept_language VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX UNIQ_4A9829E15F37A13B (token),
              INDEX IDX_opc_footprint (
                subject_type, carbon_footprint_id
              ),
              INDEX IDX_opc_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reported_emissions_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              description VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              organizational_provider_contribution_sources
            ADD
              CONSTRAINT FK_EC2F3F3EFE5E5FBD FOREIGN KEY (contribution_id) REFERENCES organizational_provider_contributions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              reported_emissions_activities
            ADD
              CONSTRAINT FK_3F561597BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_activities
            ADD
              provider_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:provider_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_activities
            ADD
              provider_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:provider_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organizational_provider_contribution_sources DROP FOREIGN KEY FK_EC2F3F3EFE5E5FBD');
        $this->addSql('ALTER TABLE reported_emissions_activities DROP FOREIGN KEY FK_3F561597BF396750');
        $this->addSql('DROP TABLE organizational_provider_contribution_sources');
        $this->addSql('DROP TABLE organizational_provider_contributions');
        $this->addSql('DROP TABLE reported_emissions_activities');
        $this->addSql('ALTER TABLE company_carbon_footprint_activities DROP provider_id');
        $this->addSql('ALTER TABLE property_carbon_footprint_activities DROP provider_id');
    }
}
