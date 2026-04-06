<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406121645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE critical_point_reason_link (
              reason_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:reason_id)',
              critical_point_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_id)',
              priority VARCHAR(255) NOT NULL,
              INDEX IDX_8144FBB6F21359BB (critical_point_id),
              PRIMARY KEY(reason_id, critical_point_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_reason_link
            ADD
              CONSTRAINT FK_8144FBB6F21359BB FOREIGN KEY (critical_point_id) REFERENCES critical_points (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critical_point_reason_link DROP FOREIGN KEY FK_8144FBB6F21359BB');
        $this->addSql('DROP TABLE critical_point_reason_link');
    }
}
