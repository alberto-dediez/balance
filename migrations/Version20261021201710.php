<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261021201710 extends AbstractMigration
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
              company_carbon_footprints
            ADD
              scope_3_excluded TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              scope_3_exclusion_reason VARCHAR(255) DEFAULT NULL,
            ADD
              scope_3_exclusion_comment LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprints
            ADD
              scope_3_excluded TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              scope_3_exclusion_reason VARCHAR(255) DEFAULT NULL,
            ADD
              scope_3_exclusion_comment LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE
              property_carbon_footprints
            SET
              scope_3_excluded = 1,
              scope_3_exclusion_reason = 'no_data_available'
            WHERE
              status = 'completed'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE
              company_carbon_footprints
            SET
              scope_3_excluded = 1,
              scope_3_exclusion_reason = 'no_data_available'
            WHERE
              status = 'completed'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_carbon_footprints
            DROP
              scope_3_excluded,
            DROP
              scope_3_exclusion_reason,
            DROP
              scope_3_exclusion_comment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprints
            DROP
              scope_3_excluded,
            DROP
              scope_3_exclusion_reason,
            DROP
              scope_3_exclusion_comment
        SQL);
    }
}
