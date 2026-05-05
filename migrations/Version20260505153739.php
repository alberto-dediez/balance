<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505153739 extends AbstractMigration
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
              prevention_plan_versions
            ADD
              responsible_employee_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:employee_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plans
            ADD
              responsible_employee_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:employee_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prevention_plan_versions DROP responsible_employee_id');
        $this->addSql('ALTER TABLE prevention_plans DROP responsible_employee_id');
    }
}
