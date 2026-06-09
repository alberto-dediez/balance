<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609092318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS importation (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:importation_id)',
              name VARCHAR(255) NOT NULL,
              type VARCHAR(255) NOT NULL,
              status VARCHAR(255) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              registered_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              default_values LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE importation');
    }
}
