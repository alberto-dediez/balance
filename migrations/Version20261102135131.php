<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102135131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_assessment_answers (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_assessment_answer_id)',
              materiality_assessment_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_assessment_id)',
              topic_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_topic_id)',
              question_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_question_id)',
              criterion VARCHAR(32) NOT NULL,
              text LONGTEXT NOT NULL,
              help LONGTEXT NOT NULL,
              answer_scale VARCHAR(16) NOT NULL,
              weight INT NOT NULL,
              position INT NOT NULL,
              question_updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              value SMALLINT DEFAULT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_materiality_assessment_answers_topic (
                materiality_assessment_id, topic_id
              ),
              UNIQUE INDEX uq_materiality_assessment_answer (
                materiality_assessment_id, topic_id,
                question_id
              ),
              INDEX IDX_D17013655096474A (materiality_assessment_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_assessment_topics (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_assessment_topic_id)',
              materiality_assessment_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_assessment_id)',
              topic_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_topic_id)',
              topic_number INT DEFAULT NULL,
              scope VARCHAR(16) NOT NULL,
              direction VARCHAR(16) DEFAULT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              position INT NOT NULL,
              points INT DEFAULT NULL,
              min_points INT DEFAULT NULL,
              max_points INT DEFAULT NULL,
              highest_magnitude_level SMALLINT DEFAULT NULL,
              UNIQUE INDEX uq_materiality_assessment_topic (
                materiality_assessment_id, topic_id
              ),
              INDEX IDX_8F35E8ED5096474A (materiality_assessment_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materiality_assessments (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:materiality_assessment_id)',
              subject_type VARCHAR(20) NOT NULL,
              carbon_footprint_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              year INT NOT NULL,
              sector_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:materiality_sector_id)',
              sector_name VARCHAR(255) DEFAULT NULL,
              status VARCHAR(16) NOT NULL,
              completed_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_materiality_assessments_subject (
                subject_type, carbon_footprint_id
              ),
              INDEX idx_materiality_assessments_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_assessment_answers
            ADD
              CONSTRAINT FK_D17013655096474A FOREIGN KEY (materiality_assessment_id) REFERENCES materiality_assessments (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiality_assessment_topics
            ADD
              CONSTRAINT FK_8F35E8ED5096474A FOREIGN KEY (materiality_assessment_id) REFERENCES materiality_assessments (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiality_assessment_answers DROP FOREIGN KEY FK_D17013655096474A');
        $this->addSql('ALTER TABLE materiality_assessment_topics DROP FOREIGN KEY FK_8F35E8ED5096474A');
        $this->addSql('DROP TABLE materiality_assessment_answers');
        $this->addSql('DROP TABLE materiality_assessment_topics');
        $this->addSql('DROP TABLE materiality_assessments');
    }
}
