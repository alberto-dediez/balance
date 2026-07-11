<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260711105646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE attendee_survey_responses (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:attendee_survey_response_id)',
              attendee_survey_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:attendee_survey_id)',
              came_from_another_city TINYINT(1) NOT NULL,
              intercity_days INT DEFAULT NULL,
              intercity_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
              intercity_vehicle_occupants INT DEFAULT NULL,
              intercity_distance_km DOUBLE PRECISION DEFAULT NULL,
              used_hotel TINYINT(1) NOT NULL,
              hotel_nights INT DEFAULT NULL,
              city_days INT DEFAULT NULL,
              city_mobility_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:mobility_id)',
              city_vehicle_occupants INT DEFAULT NULL,
              city_distance_km DOUBLE PRECISION DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_attendee_survey_responses_attendee_survey_id (attendee_survey_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE attendee_surveys (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:attendee_survey_id)',
              event_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              token VARCHAR(64) NOT NULL,
              status VARCHAR(10) NOT NULL,
              real_attendees INT DEFAULT NULL,
              confidence_level SMALLINT DEFAULT NULL,
              margin_of_error DOUBLE PRECISION DEFAULT NULL,
              closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_A93BFC5F71F7E88B (event_id),
              UNIQUE INDEX UNIQ_A93BFC5F5F37A13B (token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE attendee_survey_responses');
        $this->addSql('DROP TABLE attendee_surveys');
    }
}
