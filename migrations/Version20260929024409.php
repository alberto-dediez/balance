<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929024409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_question_sector_links (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_question_sector_link_id)',
              materiality_question_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_question_id)',
              sector_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_sector_id)',
              INDEX idx_materiality_question_sector_links_sector (sector_id),
              UNIQUE INDEX uq_materiality_question_sector_link (
                materiality_question_id, sector_id
              ),
              INDEX IDX_5CD5DE213D699DDD (materiality_question_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_sector_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              materiality_sector_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_sector_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_E5A93C1C91B76C (materiality_sector_id),
              PRIMARY KEY(locale, materiality_sector_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_sectors (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_sector_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_materiality_sectors_removed (removed),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_question_sector_links
            ADD
              CONSTRAINT FK_5CD5DE213D699DDD FOREIGN KEY (materiality_question_id) REFERENCES materiality_questions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_sector_translation
            ADD
              CONSTRAINT FK_E5A93C1C91B76C FOREIGN KEY (materiality_sector_id) REFERENCES materiality_sectors (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiality_question_sector_links DROP FOREIGN KEY FK_5CD5DE213D699DDD');
        $this->addSql('ALTER TABLE materiality_sector_translation DROP FOREIGN KEY FK_E5A93C1C91B76C');
        $this->addSql('DROP TABLE materiality_question_sector_links');
        $this->addSql('DROP TABLE materiality_sector_translation');
        $this->addSql('DROP TABLE materiality_sectors');
    }
}
