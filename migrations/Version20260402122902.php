<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402122902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reason_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              reason_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:reason_id)',
              description VARCHAR(1024) NOT NULL,
              INDEX IDX_13B9FB0859BB1592 (reason_id),
              PRIMARY KEY(locale, reason_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reasons (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:reason_id)',
              description VARCHAR(1024) NOT NULL,
              priority VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              reason_translation
            ADD
              CONSTRAINT FK_13B9FB0859BB1592 FOREIGN KEY (reason_id) REFERENCES reasons (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reason_translation DROP FOREIGN KEY FK_13B9FB0859BB1592');
        $this->addSql('DROP TABLE reason_translation');
        $this->addSql('DROP TABLE reasons');
    }
}
