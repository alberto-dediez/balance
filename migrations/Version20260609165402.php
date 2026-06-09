<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609165402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE exportation (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:exportation_id)',
              type VARCHAR(255) NOT NULL,
              status VARCHAR(255) NOT NULL,
              format VARCHAR(255) NOT NULL,
              columns JSON NOT NULL,
              filter_criteria JSON NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              valid_until DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              registered_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              error_message LONGTEXT DEFAULT NULL,
              file_path VARCHAR(255) DEFAULT NULL,
              file_extension VARCHAR(16) DEFAULT NULL,
              INDEX idx_exportation_valid_until (valid_until),
              INDEX idx_exportation_account_id (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE exportation');
    }
}
