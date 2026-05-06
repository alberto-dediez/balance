<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506094140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_version_translations (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              prevention_plan_version_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_version_id)',
              pdf_path VARCHAR(1024) NOT NULL,
              INDEX IDX_2BC2E1CD4FE71F30 (prevention_plan_version_id),
              PRIMARY KEY(
                locale, prevention_plan_version_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_version_translations
            ADD
              CONSTRAINT FK_2BC2E1CD4FE71F30 FOREIGN KEY (prevention_plan_version_id) REFERENCES prevention_plan_versions (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prevention_plan_version_translations DROP FOREIGN KEY FK_2BC2E1CD4FE71F30');
        $this->addSql('DROP TABLE prevention_plan_version_translations');
    }
}
