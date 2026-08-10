<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810081455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE payments (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:payment_id)',
              subject_type VARCHAR(64) NOT NULL,
              subject_id VARCHAR(36) NOT NULL,
              description VARCHAR(255) NOT NULL,
              amount DOUBLE PRECISION NOT NULL,
              currency VARCHAR(3) NOT NULL,
              customer_email VARCHAR(255) NOT NULL,
              customer_name VARCHAR(255) NOT NULL,
              status VARCHAR(20) NOT NULL,
              gateway VARCHAR(30) DEFAULT NULL,
              external_id VARCHAR(255) DEFAULT NULL,
              external_payment_id VARCHAR(255) DEFAULT NULL,
              failure_reason LONGTEXT DEFAULT NULL,
              started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_65D29B329F75D7B0 (external_id),
              INDEX idx_payment_subject (subject_type, subject_id),
              INDEX idx_payment_status (status),
              INDEX idx_payment_external_id (external_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_contribution_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              project_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_id)',
              origin VARCHAR(40) NOT NULL,
              origin_id VARCHAR(36) DEFAULT NULL,
              co2eq_kg DOUBLE PRECISION DEFAULT NULL,
              tonnes DOUBLE PRECISION NOT NULL,
              price_per_tonne DOUBLE PRECISION NOT NULL,
              credit_cost_per_tonne DOUBLE PRECISION DEFAULT NULL,
              free_amount TINYINT(1) NOT NULL,
              contribution_amount DOUBLE PRECISION NOT NULL,
              management_fee_amount DOUBLE PRECISION NOT NULL,
              taxable_amount DOUBLE PRECISION NOT NULL,
              vat_rate DOUBLE PRECISION NOT NULL,
              vat_amount DOUBLE PRECISION NOT NULL,
              total_amount DOUBLE PRECISION NOT NULL,
              vat_reason VARCHAR(30) DEFAULT NULL,
              currency VARCHAR(3) NOT NULL,
              contributor_name VARCHAR(255) NOT NULL,
              contributor_email VARCHAR(255) NOT NULL,
              contributor_country VARCHAR(2) NOT NULL,
              contributor_is_company TINYINT(1) NOT NULL,
              contributor_company_name VARCHAR(255) DEFAULT NULL,
              contributor_vat_number VARCHAR(40) DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              payment_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:payment_id)',
              failure_reason LONGTEXT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_project_contribution_balance (account_id, project_id, status),
              INDEX idx_project_contribution_expiration (status, expires_at),
              INDEX idx_project_contribution_origin (origin, origin_id),
              INDEX idx_project_contribution_payment (payment_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE projects ADD price_per_tonne DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE project_contributions');
        $this->addSql('ALTER TABLE projects DROP price_per_tonne');
    }
}
