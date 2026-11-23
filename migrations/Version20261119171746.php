<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119171746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE media_folders (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:folder_id)',
              name VARCHAR(255) NOT NULL,
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_media_folder_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE media ADD folder_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:folder_id)\'');
        $this->addSql('CREATE INDEX idx_media_folder ON media (folder_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE media_folders');
        $this->addSql('DROP INDEX idx_media_folder ON media');
        $this->addSql('ALTER TABLE media DROP folder_id');
    }
}
