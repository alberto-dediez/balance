<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260917074340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bioscore_catalog_items (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:bioscore_catalog_item_id)',
              catalog VARCHAR(32) NOT NULL,
              bioscore_id INT NOT NULL,
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(50) NOT NULL,
              target_id VARCHAR(36) DEFAULT NULL,
              status VARCHAR(32) NOT NULL,
              withdrawn TINYINT(1) NOT NULL,
              first_seen_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_seen_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              mapped_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              withdrawn_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_bioscore_catalog_items_status (catalog, status, withdrawn),
              INDEX idx_bioscore_catalog_items_target (target_id),
              UNIQUE INDEX uq_bioscore_catalog_items_catalog_bioscore_id (catalog, bioscore_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bioscore_catalog_items');
    }
}
