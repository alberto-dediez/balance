<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104124143 extends AbstractMigration
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
              account
            ADD
              feature_account_trip_calculator TINYINT(1) NOT NULL,
            ADD
              feature_property_trip_calculators TINYINT(1) NOT NULL,
            ADD
              feature_events TINYINT(1) NOT NULL,
            ADD
              feature_compensation TINYINT(1) NOT NULL,
            ADD
              feature_organizational_carbon_footprint TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE
              account
            SET
              feature_account_trip_calculator = 1,
              feature_property_trip_calculators = 1,
              feature_events = 1,
              feature_compensation = 1,
              feature_organizational_carbon_footprint = 1
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              account
            DROP
              feature_account_trip_calculator,
            DROP
              feature_property_trip_calculators,
            DROP
              feature_events,
            DROP
              feature_compensation,
            DROP
              feature_organizational_carbon_footprint
        SQL);
    }
}
