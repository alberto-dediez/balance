<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625103916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE material_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              material_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:material_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_17757767E308AC6F (material_id),
              PRIMARY KEY(locale, material_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE materials (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:material_id)',
              name VARCHAR(255) NOT NULL,
              category VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_translation
            ADD
              CONSTRAINT FK_17757767E308AC6F FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE material_translation DROP FOREIGN KEY FK_17757767E308AC6F');
        $this->addSql('DROP TABLE material_translation');
        $this->addSql('DROP TABLE materials');
    }
}
