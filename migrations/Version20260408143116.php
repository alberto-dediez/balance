<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408143116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_proposed_actions (
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              action_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:action_id)',
              prevention_plan_version_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_version_id)',
              INDEX IDX_3C3C86E04FE71F30 (prevention_plan_version_id),
              PRIMARY KEY(
                property_id, action_id, prevention_plan_version_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_versions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_version_id)',
              prevention_plan_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_id)',
              version_number INT NOT NULL,
              created_by VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_629B0D47C44547A6 (prevention_plan_id),
              UNIQUE INDEX uq_prevention_plan_version (
                prevention_plan_id, version_number
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_proposed_actions
            ADD
              CONSTRAINT FK_3C3C86E04FE71F30 FOREIGN KEY (prevention_plan_version_id) REFERENCES prevention_plan_versions (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_versions
            ADD
              CONSTRAINT FK_629B0D47C44547A6 FOREIGN KEY (prevention_plan_id) REFERENCES prevention_plans (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE prevention_plans ADD current_version INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prevention_plan_proposed_actions DROP FOREIGN KEY FK_3C3C86E04FE71F30');
        $this->addSql('ALTER TABLE prevention_plan_versions DROP FOREIGN KEY FK_629B0D47C44547A6');
        $this->addSql('DROP TABLE prevention_plan_proposed_actions');
        $this->addSql('DROP TABLE prevention_plan_versions');
        $this->addSql('ALTER TABLE prevention_plans DROP current_version');
    }
}
