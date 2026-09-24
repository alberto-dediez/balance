<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924111031 extends AbstractMigration
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
              user
            ADD
              two_factor_method VARCHAR(16) DEFAULT NULL COMMENT '(DC2Type:two_factor_method)',
            ADD
              two_factor_totp_secret VARCHAR(255) DEFAULT NULL,
            ADD
              two_factor_pending_totp_secret VARCHAR(255) DEFAULT NULL,
            ADD
              two_factor_code_hash VARCHAR(64) DEFAULT NULL,
            ADD
              two_factor_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              two_factor_attempts SMALLINT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            DROP
              two_factor_method,
            DROP
              two_factor_totp_secret,
            DROP
              two_factor_pending_totp_secret,
            DROP
              two_factor_code_hash,
            DROP
              two_factor_expires_at,
            DROP
              two_factor_attempts
        SQL);
    }
}
