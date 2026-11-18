<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111111125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_action_plan (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_action_plan_id)',
              conversation_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_conversation_id)',
              user_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              summary LONGTEXT NOT NULL,
              timezone VARCHAR(64) NOT NULL,
              status VARCHAR(10) NOT NULL,
              failure_reason LONGTEXT DEFAULT NULL,
              discard_reason VARCHAR(10) DEFAULT NULL,
              approved_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              proposed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              approved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              finished_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_progress_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              rejected_calls INT NOT NULL,
              version INT DEFAULT 1 NOT NULL,
              model VARCHAR(64) DEFAULT NULL,
              input_tokens INT NOT NULL,
              output_tokens INT NOT NULL,
              cache_read_tokens INT NOT NULL,
              cache_creation_tokens INT NOT NULL,
              cost_usd NUMERIC(10, 6) NOT NULL,
              duration_ms INT NOT NULL,
              INDEX idx_assistant_action_plan_conversation (conversation_id),
              INDEX idx_assistant_action_plan_status (status, proposed_at),
              INDEX idx_assistant_action_plan_user (user_id, proposed_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_conversation (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_conversation_id)',
              user_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              title VARCHAR(120) NOT NULL,
              started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              last_activity_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_assistant_conversation_user_activity (user_id, last_activity_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_message (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_message_id)',
              conversation_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_conversation_id)',
              position INT NOT NULL,
              role VARCHAR(10) NOT NULL,
              kind VARCHAR(10) NOT NULL,
              status VARCHAR(10) NOT NULL,
              text LONGTEXT NOT NULL,
              error LONGTEXT DEFAULT NULL,
              screen_path VARCHAR(512) DEFAULT NULL,
              screen_title VARCHAR(255) DEFAULT NULL,
              timezone VARCHAR(64) DEFAULT NULL,
              plan_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:assistant_action_plan_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              model VARCHAR(64) DEFAULT NULL,
              input_tokens INT NOT NULL,
              output_tokens INT NOT NULL,
              cache_read_tokens INT NOT NULL,
              cache_creation_tokens INT NOT NULL,
              cost_usd NUMERIC(10, 6) NOT NULL,
              duration_ms INT NOT NULL,
              INDEX idx_assistant_message_status (status, created_at),
              UNIQUE INDEX uniq_assistant_message_position (conversation_id, position),
              INDEX IDX_8A36E1EF9AC0396 (conversation_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_plan_step (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_plan_step_id)',
              plan_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_action_plan_id)',
              position INT NOT NULL,
              tool VARCHAR(64) NOT NULL,
              label VARCHAR(255) NOT NULL,
              status VARCHAR(10) NOT NULL,
              result_summary LONGTEXT DEFAULT NULL,
              error LONGTEXT DEFAULT NULL,
              link_label VARCHAR(255) DEFAULT NULL,
              link_route VARCHAR(128) DEFAULT NULL,
              executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uniq_assistant_plan_step_position (plan_id, position),
              INDEX IDX_D6FA661DE899029B (plan_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_plan_step_argument (
              id INT AUTO_INCREMENT NOT NULL,
              step_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_plan_step_id)',
              name VARCHAR(64) NOT NULL,
              position INT NOT NULL,
              value LONGTEXT NOT NULL,
              display VARCHAR(255) DEFAULT NULL,
              from_step INT DEFAULT NULL,
              INDEX idx_assistant_plan_step_argument_step (step_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_plan_step_output (
              id INT AUTO_INCREMENT NOT NULL,
              step_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_plan_step_id)',
              name VARCHAR(64) NOT NULL,
              value VARCHAR(255) NOT NULL,
              link_parameter TINYINT(1) NOT NULL,
              INDEX idx_assistant_plan_step_output_step (step_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_tool_call (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_tool_call_id)',
              message_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_message_id)',
              position INT NOT NULL,
              tool VARCHAR(64) NOT NULL,
              summary LONGTEXT NOT NULL,
              failed TINYINT(1) NOT NULL,
              duration_ms INT NOT NULL,
              INDEX idx_assistant_tool_call_message (message_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE assistant_tool_call_argument (
              id INT AUTO_INCREMENT NOT NULL,
              tool_call_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:assistant_tool_call_id)',
              name VARCHAR(64) NOT NULL,
              position INT NOT NULL,
              value LONGTEXT NOT NULL,
              INDEX idx_assistant_tool_call_argument_call (tool_call_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_message
            ADD
              CONSTRAINT FK_8A36E1EF9AC0396 FOREIGN KEY (conversation_id) REFERENCES assistant_conversation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_plan_step
            ADD
              CONSTRAINT FK_D6FA661DE899029B FOREIGN KEY (plan_id) REFERENCES assistant_action_plan (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_plan_step_argument
            ADD
              CONSTRAINT FK_6CB3E8D573B21E9C FOREIGN KEY (step_id) REFERENCES assistant_plan_step (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_plan_step_output
            ADD
              CONSTRAINT FK_ECF24CED73B21E9C FOREIGN KEY (step_id) REFERENCES assistant_plan_step (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_tool_call
            ADD
              CONSTRAINT FK_97593BA6537A1329 FOREIGN KEY (message_id) REFERENCES assistant_message (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assistant_tool_call_argument
            ADD
              CONSTRAINT FK_DAE640B844CC966F FOREIGN KEY (tool_call_id) REFERENCES assistant_tool_call (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE recorded_events ADD assistant_execution_id VARCHAR(36) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_recorded_events_assistant_execution ON recorded_events (assistant_execution_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assistant_message DROP FOREIGN KEY FK_8A36E1EF9AC0396');
        $this->addSql('ALTER TABLE assistant_plan_step DROP FOREIGN KEY FK_D6FA661DE899029B');
        $this->addSql('ALTER TABLE assistant_plan_step_argument DROP FOREIGN KEY FK_6CB3E8D573B21E9C');
        $this->addSql('ALTER TABLE assistant_plan_step_output DROP FOREIGN KEY FK_ECF24CED73B21E9C');
        $this->addSql('ALTER TABLE assistant_tool_call DROP FOREIGN KEY FK_97593BA6537A1329');
        $this->addSql('ALTER TABLE assistant_tool_call_argument DROP FOREIGN KEY FK_DAE640B844CC966F');
        $this->addSql('DROP TABLE assistant_action_plan');
        $this->addSql('DROP TABLE assistant_conversation');
        $this->addSql('DROP TABLE assistant_message');
        $this->addSql('DROP TABLE assistant_plan_step');
        $this->addSql('DROP TABLE assistant_plan_step_argument');
        $this->addSql('DROP TABLE assistant_plan_step_output');
        $this->addSql('DROP TABLE assistant_tool_call');
        $this->addSql('DROP TABLE assistant_tool_call_argument');
        $this->addSql('DROP INDEX idx_recorded_events_assistant_execution ON recorded_events');
        $this->addSql('ALTER TABLE recorded_events DROP assistant_execution_id');
    }
}
