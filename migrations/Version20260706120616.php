<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706120616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE combustible_activities ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE electricity_activities ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE machinery_fuel_activities ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE overnight_stay_activities ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE road_transport_activities ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE waste_activities ADD source VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE combustible_activities DROP source');
        $this->addSql('ALTER TABLE electricity_activities DROP source');
        $this->addSql('ALTER TABLE machinery_fuel_activities DROP source');
        $this->addSql('ALTER TABLE overnight_stay_activities DROP source');
        $this->addSql('ALTER TABLE road_transport_activities DROP source');
        $this->addSql('ALTER TABLE waste_activities DROP source');
    }
}
