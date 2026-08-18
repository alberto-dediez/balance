<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818062400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ledger_entries (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:ledger_entry_id)',
              type VARCHAR(30) NOT NULL,
              beneficiary VARCHAR(20) NOT NULL,
              beneficiary_id VARCHAR(36) DEFAULT NULL,
              amount DOUBLE PRECISION NOT NULL,
              currency VARCHAR(3) NOT NULL,
              project_contribution_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:project_contribution_id)',
              project_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:project_id)',
              credit_purchase_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:credit_purchase_id)',
              tonnes DOUBLE PRECISION DEFAULT NULL,
              reverses_entry_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:ledger_entry_id)',
              concept LONGTEXT DEFAULT NULL,
              recorded_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              payout_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:payout_id)',
              occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              settled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_ledger_entry_pending (
                beneficiary, beneficiary_id, payout_id
              ),
              INDEX idx_ledger_entry_contribution (project_contribution_id),
              INDEX idx_ledger_entry_payout (payout_id),
              INDEX idx_ledger_entry_occurred_at (occurred_at),
              INDEX idx_ledger_entry_type (type),
              INDEX idx_ledger_entry_credit_purchase (credit_purchase_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payees (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:payee_id)',
              type VARCHAR(20) DEFAULT NULL,
              subject_id VARCHAR(36) DEFAULT NULL,
              legal_name VARCHAR(255) NOT NULL,
              tax_id VARCHAR(50) DEFAULT NULL,
              iban VARCHAR(50) DEFAULT NULL,
              bic VARCHAR(20) DEFAULT NULL,
              holder_name VARCHAR(255) DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              external_id VARCHAR(255) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              street VARCHAR(512) DEFAULT NULL,
              postal_code VARCHAR(20) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              country VARCHAR(255) DEFAULT NULL,
              INDEX idx_payee_status (status),
              INDEX idx_payee_subject_id (subject_id),
              UNIQUE INDEX uniq_payee_subject (type, subject_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payouts (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:payout_id)',
              number VARCHAR(20) DEFAULT NULL,
              payee_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:payee_id)',
              base_amount DOUBLE PRECISION NOT NULL,
              payable_amount DOUBLE PRECISION NOT NULL,
              currency VARCHAR(3) NOT NULL,
              lines_count INT NOT NULL,
              status VARCHAR(20) NOT NULL,
              gateway VARCHAR(50) DEFAULT NULL,
              external_id VARCHAR(255) DEFAULT NULL,
              reference VARCHAR(255) DEFAULT NULL,
              evidence VARCHAR(36) DEFAULT NULL,
              failure_reason LONGTEXT DEFAULT NULL,
              created_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              resolved_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              notes LONGTEXT DEFAULT NULL,
              period_from DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              period_to DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_F54E808E96901F54 (number),
              INDEX idx_payout_payee_id (payee_id),
              INDEX idx_payout_status (status),
              INDEX idx_payout_created_at (created_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE credit_purchases ADD amount DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ledger_entries');
        $this->addSql('DROP TABLE payees');
        $this->addSql('DROP TABLE payouts');
        $this->addSql('ALTER TABLE credit_purchases DROP amount');
    }
}
