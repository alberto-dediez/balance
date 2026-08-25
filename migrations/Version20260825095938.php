<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260825095938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendee_survey_responses ADD hotel_people INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mobilities ADD factor_basis VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE trip_calculations ADD people INT DEFAULT NULL');
        $this->addSql('UPDATE trip_calculations SET people = 1');
        $this->addSql('UPDATE mobilities SET factor_basis = \'per_passenger\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendee_survey_responses DROP hotel_people');
        $this->addSql('ALTER TABLE mobilities DROP factor_basis');
        $this->addSql('ALTER TABLE trip_calculations DROP people');
    }
}
