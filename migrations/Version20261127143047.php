<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261127143047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contest_allowed_countries (
                id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_allowed_country_id)',
                contest_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_id)',
                country_iso VARCHAR(2) NOT NULL,
                UNIQUE INDEX uq_contest_allowed_country (contest_id, country_iso),
                INDEX IDX_1FDA40161CD0F0DE (contest_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contest_entries (
                id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_entry_id)',
                contest_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_id)',
                account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
                host_type VARCHAR(10) NOT NULL,
                host_id VARCHAR(36) NOT NULL,
                origin VARCHAR(40) NOT NULL,
                origin_id VARCHAR(36) NOT NULL,
                project_contribution_id VARCHAR(36) DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) DEFAULT NULL,
                phone VARCHAR(40) DEFAULT NULL,
                country VARCHAR(2) DEFAULT NULL,
                locale VARCHAR(5) NOT NULL,
                status VARCHAR(20) NOT NULL,
                entry_number INT DEFAULT NULL,
                winner TINYINT(1) NOT NULL,
                registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                confirmed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                awarded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                disqualified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                disqualified_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
                disqualification_reason VARCHAR(255) DEFAULT NULL,
                personal_data_purged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                consent_consented_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                consent_policy_version VARCHAR(64) DEFAULT NULL,
                consent_locale VARCHAR(5) DEFAULT NULL,
                consent_ip VARCHAR(45) DEFAULT NULL,
                consent_user_agent VARCHAR(512) DEFAULT NULL,
                consent_accept_language VARCHAR(255) DEFAULT NULL,
                INDEX idx_contest_entry_contest_status (contest_id, status, winner),
                INDEX idx_contest_entry_project_contribution (project_contribution_id),
                UNIQUE INDEX uq_contest_entry_origin (contest_id, origin, origin_id),
                UNIQUE INDEX uq_contest_entry_email (contest_id, email),
                UNIQUE INDEX uq_contest_entry_number (contest_id, entry_number),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contest_hosts (
                id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_host_id)',
                contest_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_id)',
                host_type VARCHAR(10) NOT NULL,
                host_id VARCHAR(36) NOT NULL,
                INDEX idx_contest_host_host (host_type, host_id),
                UNIQUE INDEX uq_contest_host (contest_id, host_type, host_id),
                INDEX IDX_D4B016911CD0F0DE (contest_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contests (
                id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_id)',
                account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
                participation_mode VARCHAR(20) NOT NULL,
                award_mode VARCHAR(20) NOT NULL,
                winners_count INT DEFAULT NULL,
                platform_draw TINYINT(1) NOT NULL,
                adults_only TINYINT(1) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                prize_description VARCHAR(255) NOT NULL,
                terms LONGTEXT NOT NULL,
                organizer_name VARCHAR(255) NOT NULL,
                organizer_tax_id VARCHAR(40) NOT NULL,
                organizer_contact_email VARCHAR(255) NOT NULL,
                privacy_policy_url VARCHAR(255) DEFAULT NULL,
                starts_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                ends_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                status VARCHAR(20) NOT NULL,
                confirmed_entries INT NOT NULL,
                responsibility_accepted_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
                responsibility_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                drawn_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
                draw_candidates INT DEFAULT NULL,
                redrawn_prizes INT NOT NULL,
                personal_data_purged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                personal_data_purged_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
                registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_contest_account_status (account_id, status),
                INDEX idx_contest_status_ends_at (status, ends_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contests_translation (
                locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
                contest_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contest_id)',
                name VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                prize_description VARCHAR(255) NOT NULL,
                terms LONGTEXT NOT NULL,
                INDEX IDX_7FF4E4631CD0F0DE (contest_id),
                PRIMARY KEY(locale, contest_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
                contest_allowed_countries
            ADD
                CONSTRAINT FK_1FDA40161CD0F0DE FOREIGN KEY (contest_id) REFERENCES contests (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
                contest_hosts
            ADD
                CONSTRAINT FK_D4B016911CD0F0DE FOREIGN KEY (contest_id) REFERENCES contests (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
                contests_translation
            ADD
                CONSTRAINT FK_7FF4E4631CD0F0DE FOREIGN KEY (contest_id) REFERENCES contests (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE account ADD feature_contests TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contest_allowed_countries DROP FOREIGN KEY FK_1FDA40161CD0F0DE');
        $this->addSql('ALTER TABLE contest_hosts DROP FOREIGN KEY FK_D4B016911CD0F0DE');
        $this->addSql('ALTER TABLE contests_translation DROP FOREIGN KEY FK_7FF4E4631CD0F0DE');
        $this->addSql('DROP TABLE contest_allowed_countries');
        $this->addSql('DROP TABLE contest_entries');
        $this->addSql('DROP TABLE contest_hosts');
        $this->addSql('DROP TABLE contests');
        $this->addSql('DROP TABLE contests_translation');
        $this->addSql('ALTER TABLE account DROP feature_contests');
    }
}
