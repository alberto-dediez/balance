<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923090142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE announcement_translations (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              announcement_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:announcement_id)',
              title VARCHAR(255) NOT NULL,
              summary LONGTEXT NOT NULL,
              body LONGTEXT NOT NULL,
              youtube_video_id VARCHAR(11) DEFAULT NULL COMMENT '(DC2Type:youtube_video)',
              manual_file_name VARCHAR(255) DEFAULT NULL,
              manual_path VARCHAR(255) DEFAULT NULL,
              INDEX IDX_3CC66464913AEA17 (announcement_id),
              PRIMARY KEY(locale, announcement_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE announcements (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:announcement_id)',
              category VARCHAR(255) NOT NULL,
              date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
              version VARCHAR(50) DEFAULT NULL,
              title VARCHAR(255) NOT NULL,
              summary LONGTEXT NOT NULL,
              body LONGTEXT NOT NULL,
              youtube_video_id VARCHAR(11) DEFAULT NULL COMMENT '(DC2Type:youtube_video)',
              manual_file_name VARCHAR(255) DEFAULT NULL,
              manual_path VARCHAR(255) DEFAULT NULL,
              published TINYINT(1) NOT NULL,
              published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              scheduled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              notify_on_publish TINYINT(1) NOT NULL,
              registered_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_announcement_published_date (published, date),
              INDEX idx_announcement_scheduled (published, scheduled_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              announcement_translations
            ADD
              CONSTRAINT FK_3CC66464913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcements (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement_translations DROP FOREIGN KEY FK_3CC66464913AEA17');
        $this->addSql('DROP TABLE announcement_translations');
        $this->addSql('DROP TABLE announcements');
    }
}
