<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529113459 extends AbstractMigration
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
              signature_requests
            ADD
              proof_acceptance_text_sha256 VARCHAR(64) DEFAULT NULL,
            ADD
              otp_code_hash VARCHAR(64) DEFAULT NULL,
            ADD
              otp_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              otp_attempts SMALLINT DEFAULT NULL,
            ADD
              otp_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_signature_request_token_hash ON signature_requests (token_hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_signature_request_token_hash ON signature_requests');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              signature_requests
            DROP
              proof_acceptance_text_sha256,
            DROP
              otp_code_hash,
            DROP
              otp_expires_at,
            DROP
              otp_attempts,
            DROP
              otp_verified_at
        SQL);
    }
}
