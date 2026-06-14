<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614070914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:notification_id)',
              recipient_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              channel VARCHAR(255) NOT NULL,
              subject VARCHAR(255) NOT NULL,
              body LONGTEXT NOT NULL,
              status VARCHAR(255) NOT NULL,
              read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              sent_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              failure_reason LONGTEXT DEFAULT NULL,
              INDEX idx_notification_recipient_status (recipient_id, status),
              INDEX idx_notification_recipient_read (recipient_id, read_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE notification');
    }
}
