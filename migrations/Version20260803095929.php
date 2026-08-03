<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803095929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              location VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:destination_id)',
              gallery JSON NOT NULL,
              project_type VARCHAR(255) DEFAULT NULL,
              project_status VARCHAR(255) DEFAULT NULL,
              miteco_id VARCHAR(255) DEFAULT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              project_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:project_id)',
              name VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              INDEX IDX_E9B7E1FC166D1F9C (project_id),
              PRIMARY KEY(locale, project_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects_translation
            ADD
              CONSTRAINT FK_E9B7E1FC166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projects_translation DROP FOREIGN KEY FK_E9B7E1FC166D1F9C');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE projects_translation');
    }
}
