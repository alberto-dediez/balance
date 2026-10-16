<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261016194147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transport: rename road_transport tables to transport, add weight and distance to transport activities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE road_transport_emission_factor DROP FOREIGN KEY FK_3478DE3E6C5E3E99');
        $this->addSql('ALTER TABLE road_transport_translation DROP FOREIGN KEY FK_30DF047B6C5E3E99');
        $this->addSql('ALTER TABLE road_transport_activities DROP FOREIGN KEY FK_BDF165F6BF396750');

        $this->addSql('RENAME TABLE road_transports TO transports');
        $this->addSql('RENAME TABLE road_transport_translation TO transport_translation');
        $this->addSql('RENAME TABLE road_transport_emission_factor TO transport_emission_factor');
        $this->addSql('RENAME TABLE road_transport_activities TO transport_activities');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              transports
            CHANGE
              id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:transport_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_emission_factor
            CHANGE
              id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:transport_emission_factor_id)',
            CHANGE
              road_transport_id transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:transport_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_translation
            CHANGE
              road_transport_id transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:transport_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_activities
            CHANGE
              road_transport_id transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:transport_id)',
            ADD
              weight_value DOUBLE PRECISION DEFAULT NULL,
            ADD
              weight_unit VARCHAR(255) DEFAULT NULL,
            ADD
              distance_km DOUBLE PRECISION DEFAULT NULL
        SQL);

        $this->addSql('ALTER TABLE transport_emission_factor RENAME INDEX uq_road_transport_emission_factor_year TO uq_transport_emission_factor_year');
        $this->addSql('ALTER TABLE transport_emission_factor RENAME INDEX IDX_3478DE3E6C5E3E99 TO IDX_1670911E9909C13F');
        $this->addSql('ALTER TABLE transport_translation RENAME INDEX IDX_30DF047B6C5E3E99 TO IDX_2207A6689909C13F');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_emission_factor
            ADD
              CONSTRAINT FK_1670911E9909C13F FOREIGN KEY (transport_id) REFERENCES transports (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_translation
            ADD
              CONSTRAINT FK_2207A6689909C13F FOREIGN KEY (transport_id) REFERENCES transports (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_activities
            ADD
              CONSTRAINT FK_D2BA26AFBF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);

        $this->addSql("UPDATE activities SET type = 'transport' WHERE type = 'road_transport'");
        $this->addSql(<<<'SQL'
            UPDATE
              recorded_events
            SET
              event_name = CONCAT('transport', SUBSTRING(event_name, LENGTH('roadTransport') + 1))
            WHERE
              context = 'carbon-footprint'
              AND event_name LIKE 'roadTransport%'
        SQL);
        $this->addSql("UPDATE importation SET type = 'transports' WHERE type = 'road_transports'");
        $this->addSql("UPDATE importation_profile SET type = 'transports' WHERE type = 'road_transports'");
        $this->addSql("UPDATE exportation SET type = 'transports' WHERE type = 'road_transports'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE exportation SET type = 'road_transports' WHERE type = 'transports'");
        $this->addSql("UPDATE importation_profile SET type = 'road_transports' WHERE type = 'transports'");
        $this->addSql("UPDATE importation SET type = 'road_transports' WHERE type = 'transports'");
        $this->addSql(<<<'SQL'
            UPDATE
              recorded_events
            SET
              event_name = CONCAT('roadTransport', SUBSTRING(event_name, LENGTH('transport') + 1))
            WHERE
              context = 'carbon-footprint'
              AND (
                event_name IN ('transportRegistered', 'transportUpdated', 'transportTranslated', 'transportRemoved')
                OR event_name LIKE 'transportEmissionFactor%'
                OR event_name LIKE 'transportActivity%'
              )
        SQL);
        $this->addSql("UPDATE activities SET type = 'road_transport' WHERE type = 'transport'");

        $this->addSql('ALTER TABLE transport_emission_factor DROP FOREIGN KEY FK_1670911E9909C13F');
        $this->addSql('ALTER TABLE transport_translation DROP FOREIGN KEY FK_2207A6689909C13F');
        $this->addSql('ALTER TABLE transport_activities DROP FOREIGN KEY FK_D2BA26AFBF396750');

        $this->addSql('ALTER TABLE transport_emission_factor RENAME INDEX uq_transport_emission_factor_year TO uq_road_transport_emission_factor_year');
        $this->addSql('ALTER TABLE transport_emission_factor RENAME INDEX IDX_1670911E9909C13F TO IDX_3478DE3E6C5E3E99');
        $this->addSql('ALTER TABLE transport_translation RENAME INDEX IDX_2207A6689909C13F TO IDX_30DF047B6C5E3E99');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_activities
            DROP
              weight_value,
            DROP
              weight_unit,
            DROP
              distance_km,
            CHANGE
              transport_id road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_translation
            CHANGE
              transport_id road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transport_emission_factor
            CHANGE
              transport_id road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)',
            CHANGE
              id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_emission_factor_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              transports
            CHANGE
              id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)'
        SQL);

        $this->addSql('RENAME TABLE transport_activities TO road_transport_activities');
        $this->addSql('RENAME TABLE transport_emission_factor TO road_transport_emission_factor');
        $this->addSql('RENAME TABLE transport_translation TO road_transport_translation');
        $this->addSql('RENAME TABLE transports TO road_transports');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_emission_factor
            ADD
              CONSTRAINT FK_3478DE3E6C5E3E99 FOREIGN KEY (road_transport_id) REFERENCES road_transports (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_translation
            ADD
              CONSTRAINT FK_30DF047B6C5E3E99 FOREIGN KEY (road_transport_id) REFERENCES road_transports (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_activities
            ADD
              CONSTRAINT FK_BDF165F6BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
    }
}
