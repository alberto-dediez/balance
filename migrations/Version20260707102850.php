<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707102850 extends AbstractMigration
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
              electricity_activities
            CHANGE
              electricity_marketer_id electricity_marketer_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:electricity_marketer_id)',
            CHANGE
              gdo_factor_id gdo_factor_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:gdo_factor_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              electricity_activities
            CHANGE
              electricity_marketer_id electricity_marketer_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:electricity_marketer_id)',
            CHANGE
              gdo_factor_id gdo_factor_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:gdo_factor_id)'
        SQL);
    }
}
