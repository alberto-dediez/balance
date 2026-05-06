<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506121034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE critical_point_surplus_causes (
              surplus_cause_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:surplus_cause_id)',
              critical_point_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_id)',
              INDEX IDX_95CBAA60F21359BB (critical_point_id),
              PRIMARY KEY(
                surplus_cause_id, critical_point_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_surplus_causes
            ADD
              CONSTRAINT FK_95CBAA60F21359BB FOREIGN KEY (critical_point_id) REFERENCES critical_points (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critical_point_surplus_causes DROP FOREIGN KEY FK_95CBAA60F21359BB');
        $this->addSql('DROP TABLE critical_point_surplus_causes');
    }
}
