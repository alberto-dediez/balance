<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811103726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contribution_certificates (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contribution_certificate_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              project_contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_contribution_id)',
              code VARCHAR(20) NOT NULL,
              verification_token VARCHAR(64) NOT NULL,
              type VARCHAR(30) NOT NULL,
              status VARCHAR(20) NOT NULL,
              locale VARCHAR(5) NOT NULL,
              beneficiary_name VARCHAR(255) NOT NULL,
              beneficiary_is_company TINYINT(1) NOT NULL,
              beneficiary_vat_number VARCHAR(40) DEFAULT NULL,
              contact_name VARCHAR(255) NOT NULL,
              contact_email VARCHAR(255) NOT NULL,
              contributor_country VARCHAR(2) NOT NULL,
              co2eq_kg DOUBLE PRECISION DEFAULT NULL,
              tonnes DOUBLE PRECISION NOT NULL,
              contribution_amount DOUBLE PRECISION NOT NULL,
              currency VARCHAR(3) NOT NULL,
              project_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:project_id)',
              project_name VARCHAR(255) NOT NULL,
              project_location VARCHAR(255) DEFAULT NULL,
              project_registry_id VARCHAR(100) DEFAULT NULL,
              master_serials JSON NOT NULL,
              files JSON NOT NULL,
              failure_reason LONGTEXT DEFAULT NULL,
              issued_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              generated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              failed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_contribution_certificate_account (account_id),
              INDEX idx_contribution_certificate_status (status),
              UNIQUE INDEX uniq_contribution_certificate_code (code),
              UNIQUE INDEX uniq_contribution_certificate_verification_token (verification_token),
              UNIQUE INDEX uniq_contribution_certificate_contribution (project_contribution_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_contribution_credit_allocations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_contribution_credit_allocation_id)',
              project_contribution_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_contribution_id)',
              position INT NOT NULL,
              credit_purchase_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:credit_purchase_id)',
              purchase_reference VARCHAR(255) DEFAULT NULL,
              tonnes DOUBLE PRECISION NOT NULL,
              INDEX IDX_D7F3273FC66F2A4D (project_contribution_id),
              INDEX idx_contribution_credit_allocation_contribution (
                project_contribution_id, position
              ),
              INDEX idx_contribution_credit_allocation_purchase (credit_purchase_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_contribution_credit_allocations
            ADD
              CONSTRAINT FK_D7F3273FC66F2A4D FOREIGN KEY (project_contribution_id) REFERENCES project_contributions (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_contribution_credit_allocations DROP FOREIGN KEY FK_D7F3273FC66F2A4D');
        $this->addSql('DROP TABLE contribution_certificates');
        $this->addSql('DROP TABLE project_contribution_credit_allocations');
    }
}
