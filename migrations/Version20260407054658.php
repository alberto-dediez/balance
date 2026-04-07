<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407054658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_versions (
              version_number INT NOT NULL,
              prevention_plan_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_id)',
              created_by VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_629B0D47C44547A6 (prevention_plan_id),
              PRIMARY KEY(
                version_number, prevention_plan_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
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
        $this->addSql('ALTER TABLE prevention_plan_versions DROP FOREIGN KEY FK_629B0D47C44547A6');
        $this->addSql('DROP TABLE prevention_plan_versions');
        $this->addSql('ALTER TABLE prevention_plans DROP current_version');
    }
}
