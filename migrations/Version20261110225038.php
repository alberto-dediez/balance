<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110225038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE api_tokens (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:api_token_id)',
              user_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              name VARCHAR(100) NOT NULL,
              token_hash VARCHAR(64) NOT NULL,
              token_prefix VARCHAR(12) NOT NULL,
              expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              revoked_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_api_token_user (user_id),
              UNIQUE INDEX uq_api_token_token_hash (token_hash),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE account ADD api_tokens_enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE api_tokens');
        $this->addSql('ALTER TABLE account DROP api_tokens_enabled');
    }
}
