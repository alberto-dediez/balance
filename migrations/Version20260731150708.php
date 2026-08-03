<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260731150708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE media (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:media_id)',
              name VARCHAR(255) NOT NULL,
              file_name VARCHAR(255) NOT NULL,
              path VARCHAR(512) NOT NULL,
              extension VARCHAR(16) NOT NULL,
              mime_type VARCHAR(255) NOT NULL,
              type VARCHAR(255) NOT NULL,
              size INT NOT NULL,
              description LONGTEXT DEFAULT NULL,
              alternative_text VARCHAR(255) DEFAULT NULL,
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              uploaded_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE media');
    }
}
