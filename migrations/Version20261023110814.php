<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261023110814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE trip_calculation_link_destination_countries (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_destination_country_id)',
              trip_calculation_link_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_id)',
              country_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:country_id)',
              UNIQUE INDEX uniq_trip_link_destination_country (
                trip_calculation_link_id, country_id
              ),
              INDEX IDX_5BF3BAA1994CABFD (trip_calculation_link_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trip_calculation_link_destination_properties (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_destination_property_id)',
              trip_calculation_link_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:trip_calculation_link_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              UNIQUE INDEX uniq_trip_link_destination_property (
                trip_calculation_link_id, property_id
              ),
              INDEX IDX_8E73E8BD994CABFD (trip_calculation_link_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculation_link_destination_countries
            ADD
              CONSTRAINT FK_5BF3BAA1994CABFD FOREIGN KEY (trip_calculation_link_id) REFERENCES trip_calculation_links (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculation_link_destination_properties
            ADD
              CONSTRAINT FK_8E73E8BD994CABFD FOREIGN KEY (trip_calculation_link_id) REFERENCES trip_calculation_links (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculation_links
            ADD
              destination_mode VARCHAR(20) NOT NULL,
            ADD
              destination_place_kind VARCHAR(10) NOT NULL,
            ADD
              destination_region_reference VARCHAR(255) DEFAULT NULL,
            ADD
              destination_region_label VARCHAR(255) DEFAULT NULL,
            ADD
              destination_region_low_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              destination_region_low_longitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              destination_region_high_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              destination_region_high_longitude DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              property_id property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)'
        SQL);
        $this->addSql("UPDATE trip_calculation_links SET destination_mode = 'free' WHERE destination_mode = ''");
        $this->addSql("UPDATE trip_calculation_links SET destination_place_kind = 'city' WHERE destination_place_kind = ''");
        $this->addSql('CREATE INDEX idx_trip_calculation_links_account_id ON trip_calculation_links (account_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculations
            ADD
              destination_place_reference VARCHAR(255) DEFAULT NULL,
            ADD
              destination_label VARCHAR(255) DEFAULT NULL,
            ADD
              destination_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              destination_longitude DOUBLE PRECISION DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip_calculation_link_destination_countries DROP FOREIGN KEY FK_5BF3BAA1994CABFD');
        $this->addSql('ALTER TABLE trip_calculation_link_destination_properties DROP FOREIGN KEY FK_8E73E8BD994CABFD');
        $this->addSql('DROP TABLE trip_calculation_link_destination_countries');
        $this->addSql('DROP TABLE trip_calculation_link_destination_properties');
        $this->addSql('DROP INDEX idx_trip_calculation_links_account_id ON trip_calculation_links');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculation_links
            DROP
              destination_mode,
            DROP
              destination_place_kind,
            DROP
              destination_region_reference,
            DROP
              destination_region_label,
            DROP
              destination_region_low_latitude,
            DROP
              destination_region_low_longitude,
            DROP
              destination_region_high_latitude,
            DROP
              destination_region_high_longitude,
            CHANGE
              property_id property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trip_calculations
            DROP
              destination_place_reference,
            DROP
              destination_label,
            DROP
              destination_latitude,
            DROP
              destination_longitude
        SQL);
    }
}
