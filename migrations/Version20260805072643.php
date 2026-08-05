<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805072643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Splits attendee survey response mobility into legs, migrating existing intercity and city data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE attendee_survey_response_legs (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:attendee_survey_response_leg_id)',
              attendee_survey_response_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:attendee_survey_response_id)',
              position INT NOT NULL,
              kind VARCHAR(20) NOT NULL,
              mobility_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:mobility_id)',
              vehicle_occupants INT DEFAULT NULL,
              origin_place_reference VARCHAR(255) DEFAULT NULL,
              origin_label VARCHAR(255) DEFAULT NULL,
              origin_latitude DOUBLE PRECISION DEFAULT NULL,
              origin_longitude DOUBLE PRECISION DEFAULT NULL,
              destination_place_reference VARCHAR(255) DEFAULT NULL,
              destination_label VARCHAR(255) DEFAULT NULL,
              destination_latitude DOUBLE PRECISION DEFAULT NULL,
              destination_longitude DOUBLE PRECISION DEFAULT NULL,
              distance_km DOUBLE PRECISION DEFAULT NULL,
              distance_source VARCHAR(20) NOT NULL,
              INDEX IDX_87530C771E985964 (attendee_survey_response_id),
              INDEX idx_attendee_survey_response_legs_response (
                attendee_survey_response_id, position
              ),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_response_legs
            ADD
              CONSTRAINT FK_87530C771E985964 FOREIGN KEY (attendee_survey_response_id) REFERENCES attendee_survey_responses (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            ADD
              origin_place_reference VARCHAR(255) DEFAULT NULL,
            ADD
              origin_label VARCHAR(255) DEFAULT NULL,
            ADD
              origin_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              origin_longitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              accommodation_place_reference VARCHAR(255) DEFAULT NULL,
            ADD
              accommodation_label VARCHAR(255) DEFAULT NULL,
            ADD
              accommodation_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              accommodation_longitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              intercity_round_trip TINYINT(1) NOT NULL,
            ADD
              intercity_trips INT DEFAULT NULL,
            ADD
              moved_within_city TINYINT(1) NOT NULL,
            ADD
              city_origin_place_reference VARCHAR(255) DEFAULT NULL,
            ADD
              city_origin_label VARCHAR(255) DEFAULT NULL,
            ADD
              city_origin_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              city_origin_longitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              city_round_trip TINYINT(1) NOT NULL,
            ADD
              city_trips INT DEFAULT NULL
        SQL);

        // Each pre-existing response had a single intercity and a single city journey: they become
        // one leg each, keeping the manual distance. Round trip stays false and trips takes the old
        // days value, so the aggregated km per mobility remain exactly the same as before.
        $this->addSql(<<<'SQL'
            INSERT INTO attendee_survey_response_legs (
              id, attendee_survey_response_id, position, kind,
              mobility_id, vehicle_occupants, distance_km, distance_source
            )
            SELECT
              UUID(), id, 1, 'intercity',
              intercity_mobility_id, intercity_vehicle_occupants, intercity_distance_km, 'manual'
            FROM attendee_survey_responses
            WHERE intercity_mobility_id IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO attendee_survey_response_legs (
              id, attendee_survey_response_id, position, kind,
              mobility_id, vehicle_occupants, distance_km, distance_source
            )
            SELECT
              UUID(), id, 1, 'city',
              city_mobility_id, city_vehicle_occupants, city_distance_km, 'manual'
            FROM attendee_survey_responses
            WHERE city_mobility_id IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE attendee_survey_responses
            SET
              intercity_trips = intercity_days,
              intercity_round_trip = 0,
              city_trips = city_days,
              city_round_trip = 0,
              moved_within_city = IF(city_mobility_id IS NOT NULL, 1, 0)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            DROP
              intercity_days,
            DROP
              intercity_mobility_id,
            DROP
              intercity_vehicle_occupants,
            DROP
              intercity_distance_km,
            DROP
              city_days,
            DROP
              city_mobility_id,
            DROP
              city_vehicle_occupants,
            DROP
              city_distance_km
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              events
            ADD
              venue_place_reference VARCHAR(255) DEFAULT NULL,
            ADD
              venue_label VARCHAR(255) DEFAULT NULL,
            ADD
              venue_latitude DOUBLE PRECISION DEFAULT NULL,
            ADD
              venue_longitude DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE mobilities ADD travel_mode VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            ADD
              intercity_days INT DEFAULT NULL,
            ADD
              intercity_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
            ADD
              intercity_vehicle_occupants INT DEFAULT NULL,
            ADD
              intercity_distance_km DOUBLE PRECISION DEFAULT NULL,
            ADD
              city_days INT DEFAULT NULL,
            ADD
              city_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
            ADD
              city_vehicle_occupants INT DEFAULT NULL,
            ADD
              city_distance_km DOUBLE PRECISION DEFAULT NULL
        SQL);

        // The old structure only holds one journey per kind, so only the first leg survives.
        $this->addSql(<<<'SQL'
            UPDATE attendee_survey_responses r
            LEFT JOIN attendee_survey_response_legs il
              ON il.attendee_survey_response_id = r.id AND il.kind = 'intercity' AND il.position = 1
            LEFT JOIN attendee_survey_response_legs cl
              ON cl.attendee_survey_response_id = r.id AND cl.kind = 'city' AND cl.position = 1
            SET
              r.intercity_days = r.intercity_trips,
              r.intercity_mobility_id = il.mobility_id,
              r.intercity_vehicle_occupants = il.vehicle_occupants,
              r.intercity_distance_km = il.distance_km,
              r.city_days = r.city_trips,
              r.city_mobility_id = cl.mobility_id,
              r.city_vehicle_occupants = cl.vehicle_occupants,
              r.city_distance_km = cl.distance_km
        SQL);

        $this->addSql('ALTER TABLE attendee_survey_response_legs DROP FOREIGN KEY FK_87530C771E985964');
        $this->addSql('DROP TABLE attendee_survey_response_legs');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              attendee_survey_responses
            DROP
              origin_place_reference,
            DROP
              origin_label,
            DROP
              origin_latitude,
            DROP
              origin_longitude,
            DROP
              accommodation_place_reference,
            DROP
              accommodation_label,
            DROP
              accommodation_latitude,
            DROP
              accommodation_longitude,
            DROP
              intercity_round_trip,
            DROP
              intercity_trips,
            DROP
              moved_within_city,
            DROP
              city_origin_place_reference,
            DROP
              city_origin_label,
            DROP
              city_origin_latitude,
            DROP
              city_origin_longitude,
            DROP
              city_round_trip,
            DROP
              city_trips
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              events
            DROP
              venue_place_reference,
            DROP
              venue_label,
            DROP
              venue_latitude,
            DROP
              venue_longitude
        SQL);
        $this->addSql('ALTER TABLE mobilities DROP travel_mode');
    }
}
