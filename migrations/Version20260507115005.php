<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507115005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `primary` ON prevention_plan_proposed_actions');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_proposed_actions
            ADD
              id VARCHAR(36) NULL COMMENT '(DC2Type:proposed_action_id)'
        SQL);
        $this->addSql('UPDATE prevention_plan_proposed_actions SET id = UUID() WHERE id IS NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_proposed_actions
            MODIFY
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:proposed_action_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_prevention_plan_proposed_action ON prevention_plan_proposed_actions (
              prevention_plan_version_id, property_id,
              action_id, critical_point_id
            )
        SQL);
        $this->addSql('ALTER TABLE prevention_plan_proposed_actions ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_prevention_plan_proposed_action ON prevention_plan_proposed_actions');
        $this->addSql('DROP INDEX `PRIMARY` ON prevention_plan_proposed_actions');
        $this->addSql('ALTER TABLE prevention_plan_proposed_actions DROP id');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_proposed_actions
            ADD
              PRIMARY KEY (
                property_id, action_id, critical_point_id,
                prevention_plan_version_id
              )
        SQL);
    }
}
