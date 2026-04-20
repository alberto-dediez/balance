<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420171821 extends AbstractMigration
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
              critical_point_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_id)'
        SQL);
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

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `PRIMARY` ON prevention_plan_proposed_actions');
        $this->addSql('ALTER TABLE prevention_plan_proposed_actions DROP critical_point_id');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_proposed_actions
            ADD
              PRIMARY KEY (
                property_id, action_id, prevention_plan_version_id
              )
        SQL);
    }
}
