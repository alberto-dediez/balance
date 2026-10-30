<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030110757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE operator_contribution_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:operator_contribution_activity_id)',
              operator_contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:operator_contribution_id)',
              activity_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              scope VARCHAR(20) NOT NULL,
              source VARCHAR(50) NOT NULL,
              added_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_oca_contribution (operator_contribution_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE operator_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:operator_contribution_id)',
              company_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_carbon_footprint_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              source VARCHAR(50) NOT NULL,
              year INT NOT NULL,
              emission_factor_group_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              contact_email VARCHAR(255) NOT NULL,
              contact_name VARCHAR(255) DEFAULT NULL,
              contact_locale VARCHAR(5) NOT NULL,
              user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              token VARCHAR(64) NOT NULL,
              status VARCHAR(20) NOT NULL,
              precalculated TINYINT(1) DEFAULT 0 NOT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ignored_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
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
              INDEX IDX_oc_footprint (company_carbon_footprint_id),
              INDEX IDX_oc_account_property (account_id, property_id),
              UNIQUE INDEX UNIQ_oc_token (token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              operator_contribution_activities
            ADD
              CONSTRAINT FK_CF8FA12F4E15CDB9 FOREIGN KEY (operator_contribution_id) REFERENCES operator_contributions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprint_non_consolidated_properties
            ADD
              operator_contribution_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:operator_contribution_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              properties
            ADD
              operator_email VARCHAR(255) DEFAULT NULL,
            ADD
              operator_name VARCHAR(255) DEFAULT NULL,
            ADD
              operator_locale VARCHAR(5) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operator_contribution_activities DROP FOREIGN KEY FK_CF8FA12F4E15CDB9');
        $this->addSql('DROP TABLE operator_contribution_activities');
        $this->addSql('DROP TABLE operator_contributions');
        $this->addSql('ALTER TABLE company_carbon_footprint_non_consolidated_properties DROP operator_contribution_id');
        $this->addSql('ALTER TABLE properties DROP operator_email, DROP operator_name, DROP operator_locale');
    }
}
