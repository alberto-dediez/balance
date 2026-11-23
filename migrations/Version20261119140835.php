<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119140835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE integration_inbox (
              message_id VARCHAR(36) NOT NULL,
              source VARCHAR(64) NOT NULL,
              type VARCHAR(150) NOT NULL,
              version INT NOT NULL,
              aggregate_id VARCHAR(64) DEFAULT NULL,
              sequence INT NOT NULL,
              correlation_id VARCHAR(36) NOT NULL,
              causation_id VARCHAR(36) DEFAULT NULL,
              hops INT NOT NULL,
              status VARCHAR(32) NOT NULL,
              received_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_integration_inbox_received_at (received_at),
              INDEX idx_integration_inbox_correlation (correlation_id),
              PRIMARY KEY(message_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE integration_outbox (
              sequence INT AUTO_INCREMENT NOT NULL,
              message_id VARCHAR(36) NOT NULL,
              type VARCHAR(150) NOT NULL,
              version INT NOT NULL,
              aggregate_id VARCHAR(64) DEFAULT NULL,
              account_ref VARCHAR(64) DEFAULT NULL,
              correlation_id VARCHAR(36) NOT NULL,
              causation_id VARCHAR(36) DEFAULT NULL,
              hops INT NOT NULL,
              occurred_on DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              payload LONGTEXT NOT NULL,
              sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              attempts INT NOT NULL,
              last_error VARCHAR(1000) DEFAULT NULL,
              INDEX idx_integration_outbox_pending (sent_at, sequence),
              UNIQUE INDEX uniq_integration_outbox_message_id (message_id),
              PRIMARY KEY(sequence)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE integration_positions (
              source VARCHAR(64) NOT NULL,
              aggregate_id VARCHAR(64) NOT NULL,
              last_sequence INT NOT NULL,
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(source, aggregate_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE integration_inbox');
        $this->addSql('DROP TABLE integration_outbox');
        $this->addSql('DROP TABLE integration_positions');
    }
}
