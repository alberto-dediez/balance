<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528150143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE signature_requests (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:signature_request_id)',
              status VARCHAR(255) NOT NULL,
              expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              token_hash VARCHAR(64) NOT NULL,
              token_plain_value VARCHAR(255) NOT NULL,
              doc_to_sign_sha256 VARCHAR(64) DEFAULT NULL,
              doc_to_sign_size INT DEFAULT NULL,
              doc_to_sign_filename VARCHAR(512) DEFAULT NULL,
              doc_to_sign_path VARCHAR(2048) DEFAULT NULL,
              doc_signed_sha256 VARCHAR(64) DEFAULT NULL,
              doc_signed_size INT DEFAULT NULL,
              doc_signed_filename VARCHAR(512) DEFAULT NULL,
              doc_signed_path VARCHAR(2048) DEFAULT NULL,
              signer_name VARCHAR(255) NOT NULL,
              signer_locale VARCHAR(255) NOT NULL COMMENT '(DC2Type:locale)',
              signer_email VARCHAR(255) DEFAULT NULL,
              signer_phone VARCHAR(64) DEFAULT NULL,
              signer_notification_channel VARCHAR(255) NOT NULL,
              proof_signed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              proof_document_hash_at_signing VARCHAR(64) DEFAULT NULL,
              proof_signed_document_sha256 VARCHAR(64) DEFAULT NULL,
              proof_driver VARCHAR(255) DEFAULT NULL,
              proof_proof_payload LONGTEXT DEFAULT NULL,
              proof_signer_ip VARCHAR(45) DEFAULT NULL,
              proof_signer_user_agent VARCHAR(512) DEFAULT NULL,
              proof_signer_accept_language VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE signature_requests');
    }
}
