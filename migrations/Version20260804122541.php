<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804122541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE credit_purchases (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:credit_purchase_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              project_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_id)',
              requested_by VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              requested_tonnes DOUBLE PRECISION NOT NULL,
              status VARCHAR(20) NOT NULL,
              purchased_tonnes DOUBLE PRECISION DEFAULT NULL,
              amount DOUBLE PRECISION DEFAULT NULL,
              reference VARCHAR(255) DEFAULT NULL,
              certificate VARCHAR(36) DEFAULT NULL,
              resolved_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              rejection_reason LONGTEXT DEFAULT NULL,
              requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              cancelled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_6BAEC7ECAEA34913 (reference),
              INDEX idx_credit_purchase_account_id (account_id),
              INDEX idx_credit_purchase_project_id (project_id),
              INDEX idx_credit_purchase_status (status),
              INDEX idx_credit_purchase_balance (account_id, project_id, status),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_purchases');
    }
}
