<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261112122832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_message_attachment (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_message_attachment_id)',
              message_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_message_id)',
              position INT NOT NULL,
              file_name VARCHAR(255) NOT NULL,
              mime_type VARCHAR(100) NOT NULL,
              size INT NOT NULL,
              path VARCHAR(512) NOT NULL,
              INDEX idx_assistant_message_attachment_message (message_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_message_attachment
            ADD
              CONSTRAINT FK_E92938A7537A1329 FOREIGN KEY (message_id) REFERENCES assistant_message (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE assistant_tool_call ADD result LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assistant_message_attachment DROP FOREIGN KEY FK_E92938A7537A1329');
        $this->addSql('DROP TABLE assistant_message_attachment');
        $this->addSql('ALTER TABLE assistant_tool_call DROP result');
    }
}
