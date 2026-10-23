<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020170559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE email_log (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:email_log_id)',
              recipient_email VARCHAR(255) NOT NULL,
              recipient_name VARCHAR(255) NOT NULL,
              subject VARCHAR(255) NOT NULL,
              template VARCHAR(255) NOT NULL,
              provider VARCHAR(50) NOT NULL,
              provider_message_id VARCHAR(255) DEFAULT NULL,
              status VARCHAR(255) NOT NULL,
              status_reason LONGTEXT DEFAULT NULL,
              sent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              first_opened_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_opened_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              open_count INT NOT NULL,
              first_clicked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              click_count INT NOT NULL,
              spam_reported_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_event_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_email_log_sent_at (sent_at),
              INDEX idx_email_log_recipient (recipient_email),
              INDEX idx_email_log_status (status),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email_log');
    }
}
