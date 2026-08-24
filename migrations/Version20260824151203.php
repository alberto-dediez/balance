<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824151203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            ADD
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              consent_policy_version VARCHAR(64) DEFAULT NULL,
            ADD
              consent_locale VARCHAR(5) DEFAULT NULL,
            ADD
              consent_ip VARCHAR(45) DEFAULT NULL,
            ADD
              consent_user_agent VARCHAR(512) DEFAULT NULL,
            ADD
              consent_accept_language VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employee_contributions
            ADD
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              consent_policy_version VARCHAR(64) DEFAULT NULL,
            ADD
              consent_locale VARCHAR(5) DEFAULT NULL,
            ADD
              consent_ip VARCHAR(45) DEFAULT NULL,
            ADD
              consent_user_agent VARCHAR(512) DEFAULT NULL,
            ADD
              consent_accept_language VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              provider_contributions
            ADD
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              consent_policy_version VARCHAR(64) DEFAULT NULL,
            ADD
              consent_locale VARCHAR(5) DEFAULT NULL,
            ADD
              consent_ip VARCHAR(45) DEFAULT NULL,
            ADD
              consent_user_agent VARCHAR(512) DEFAULT NULL,
            ADD
              consent_accept_language VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculations
            ADD
              consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              consent_policy_version VARCHAR(64) DEFAULT NULL,
            ADD
              consent_locale VARCHAR(5) DEFAULT NULL,
            ADD
              consent_ip VARCHAR(45) DEFAULT NULL,
            ADD
              consent_user_agent VARCHAR(512) DEFAULT NULL,
            ADD
              consent_accept_language VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            DROP
              consent_consented_at,
            DROP
              consent_policy_version,
            DROP
              consent_locale,
            DROP
              consent_ip,
            DROP
              consent_user_agent,
            DROP
              consent_accept_language
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employee_contributions
            DROP
              consent_consented_at,
            DROP
              consent_policy_version,
            DROP
              consent_locale,
            DROP
              consent_ip,
            DROP
              consent_user_agent,
            DROP
              consent_accept_language
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              provider_contributions
            DROP
              consent_consented_at,
            DROP
              consent_policy_version,
            DROP
              consent_locale,
            DROP
              consent_ip,
            DROP
              consent_user_agent,
            DROP
              consent_accept_language
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculations
            DROP
              consent_consented_at,
            DROP
              consent_policy_version,
            DROP
              consent_locale,
            DROP
              consent_ip,
            DROP
              consent_user_agent,
            DROP
              consent_accept_language
        SQL);
    }
}
