<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522122626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              surpluses
            ADD
              prevention_can_be_prevented TINYINT(1) DEFAULT NULL,
            ADD
              prevention_action_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:action_id)',
            ADD
              transformation_can_be_transformed TINYINT(1) DEFAULT NULL,
            ADD
              transformation_reason_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:reason_id)',
            ADD
              human_donation_can_be_human_donated TINYINT(1) DEFAULT NULL,
            ADD
              human_donation_reason_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:reason_id)',
            ADD
              animal_feed_can_be_animal_fed TINYINT(1) DEFAULT NULL,
            ADD
              animal_feed_reason_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:reason_id)',
            ADD
              composting_can_be_composted TINYINT(1) DEFAULT NULL,
            ADD
              composting_reason_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:reason_id)',
            ADD
              elimination_can_be_eliminated TINYINT(1) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              surpluses
            DROP
              prevention_can_be_prevented,
            DROP
              prevention_action_id,
            DROP
              transformation_can_be_transformed,
            DROP
              transformation_reason_id,
            DROP
              human_donation_can_be_human_donated,
            DROP
              human_donation_reason_id,
            DROP
              animal_feed_can_be_animal_fed,
            DROP
              animal_feed_reason_id,
            DROP
              composting_can_be_composted,
            DROP
              composting_reason_id,
            DROP
              elimination_can_be_eliminated
        SQL);
    }
}
