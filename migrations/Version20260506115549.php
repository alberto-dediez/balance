<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506115549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE surplus_cause_translations (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              surplus_cause_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:surplus_cause_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_665635308F43B186 (surplus_cause_id),
              PRIMARY KEY(locale, surplus_cause_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE surplus_causes (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:surplus_cause_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              surplus_cause_translations
            ADD
              CONSTRAINT FK_665635308F43B186 FOREIGN KEY (surplus_cause_id) REFERENCES surplus_causes (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE surplus_cause_translations DROP FOREIGN KEY FK_665635308F43B186');
        $this->addSql('DROP TABLE surplus_cause_translations');
        $this->addSql('DROP TABLE surplus_causes');
    }
}
