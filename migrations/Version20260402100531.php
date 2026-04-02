<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402100531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE critical_point_action_link (
              action_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:action_id)',
              critical_point_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_id)',
              INDEX IDX_B06203E6F21359BB (critical_point_id),
              PRIMARY KEY(action_id, critical_point_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_action_link
            ADD
              CONSTRAINT FK_B06203E6F21359BB FOREIGN KEY (critical_point_id) REFERENCES critical_points (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_points
            ADD
              prevention_possible TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              human_donation_possible TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              animal_feed_possible TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              transformation_possible TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              composting_possible TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critical_point_action_link DROP FOREIGN KEY FK_B06203E6F21359BB');
        $this->addSql('DROP TABLE critical_point_action_link');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_points
            DROP
              prevention_possible,
            DROP
              human_donation_possible,
            DROP
              animal_feed_possible,
            DROP
              transformation_possible,
            DROP
              composting_possible
        SQL);
    }
}
