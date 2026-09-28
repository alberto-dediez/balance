<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928082439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              announcement_translations
            ADD
              manual_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)',
            DROP
              manual_file_name,
            DROP
              manual_path
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              announcements
            ADD
              manual_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)',
            DROP
              manual_file_name,
            DROP
              manual_path
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              announcement_translations
            ADD
              manual_file_name VARCHAR(255) DEFAULT NULL,
            ADD
              manual_path VARCHAR(255) DEFAULT NULL,
            DROP
              manual_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              announcements
            ADD
              manual_file_name VARCHAR(255) DEFAULT NULL,
            ADD
              manual_path VARCHAR(255) DEFAULT NULL,
            DROP
              manual_id
        SQL);
    }
}
