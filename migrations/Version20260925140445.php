<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260925140445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_question_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              materiality_question_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_question_id)',
              text LONGTEXT NOT NULL,
              help LONGTEXT NOT NULL,
              INDEX IDX_3D75F6913D699DDD (materiality_question_id),
              PRIMARY KEY(locale, materiality_question_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_questions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_question_id)',
              criterion VARCHAR(32) NOT NULL,
              topic_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:materiality_topic_id)',
              text LONGTEXT NOT NULL,
              help LONGTEXT NOT NULL,
              answer_scale VARCHAR(16) NOT NULL,
              weight INT NOT NULL,
              position INT NOT NULL,
              active TINYINT(1) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_materiality_questions_topic (topic_id),
              INDEX idx_materiality_questions_listing (removed, active, position),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_topic_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              materiality_topic_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_topic_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              INDEX IDX_8509E0ADA0B4EED0 (materiality_topic_id),
              PRIMARY KEY(locale, materiality_topic_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_topics (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_topic_id)',
              number INT DEFAULT NULL,
              scope VARCHAR(16) NOT NULL,
              direction VARCHAR(16) DEFAULT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              position INT NOT NULL,
              active TINYINT(1) NOT NULL,
              standard TINYINT(1) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_materiality_topics_number (number),
              INDEX idx_materiality_topics_listing (removed, active, position),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_question_translation
            ADD
              CONSTRAINT FK_3D75F6913D699DDD FOREIGN KEY (materiality_question_id) REFERENCES materiality_questions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_topic_translation
            ADD
              CONSTRAINT FK_8509E0ADA0B4EED0 FOREIGN KEY (materiality_topic_id) REFERENCES materiality_topics (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiality_question_translation DROP FOREIGN KEY FK_3D75F6913D699DDD');
        $this->addSql('ALTER TABLE materiality_topic_translation DROP FOREIGN KEY FK_8509E0ADA0B4EED0');
        $this->addSql('DROP TABLE materiality_question_translation');
        $this->addSql('DROP TABLE materiality_questions');
        $this->addSql('DROP TABLE materiality_topic_translation');
        $this->addSql('DROP TABLE materiality_topics');
    }
}
