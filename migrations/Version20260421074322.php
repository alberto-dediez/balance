<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421074322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_section_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              prevention_plan_section_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_section_id)',
              title VARCHAR(512) NOT NULL,
              content LONGTEXT NOT NULL,
              INDEX IDX_2A55081EDC78DB4F (prevention_plan_section_id),
              PRIMARY KEY(
                locale, prevention_plan_section_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_sections (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_section_id)',
              prevention_plan_version_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_version_id)',
              title VARCHAR(512) NOT NULL,
              content LONGTEXT NOT NULL,
              INDEX IDX_50D10E0D4FE71F30 (prevention_plan_version_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_section_translation
            ADD
              CONSTRAINT FK_2A55081EDC78DB4F FOREIGN KEY (prevention_plan_section_id) REFERENCES prevention_plan_sections (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_sections
            ADD
              CONSTRAINT FK_50D10E0D4FE71F30 FOREIGN KEY (prevention_plan_version_id) REFERENCES prevention_plan_versions (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prevention_plan_section_translation DROP FOREIGN KEY FK_2A55081EDC78DB4F');
        $this->addSql('ALTER TABLE prevention_plan_sections DROP FOREIGN KEY FK_50D10E0D4FE71F30');
        $this->addSql('DROP TABLE prevention_plan_section_translation');
        $this->addSql('DROP TABLE prevention_plan_sections');
    }
}
