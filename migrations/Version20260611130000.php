<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Waste BC tables (alerts, containers, measurements, waste, waste_managers, daily_wastes and related)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE waste_translation DROP FOREIGN KEY FK_7CDF8BABFA6A22C2');
        $this->addSql('ALTER TABLE alert_configuration_items DROP FOREIGN KEY FK_4BB2623B701B6C9A');

        $this->addSql('DROP TABLE IF EXISTS waste_translation');
        $this->addSql('DROP TABLE IF EXISTS alert_configuration_items');
        $this->addSql('DROP TABLE IF EXISTS alert_configurations');
        $this->addSql('DROP TABLE IF EXISTS alert_check_queue');
        $this->addSql('DROP TABLE IF EXISTS alerts');
        $this->addSql('DROP TABLE IF EXISTS containers');
        $this->addSql('DROP TABLE IF EXISTS daily_wastes');
        $this->addSql('DROP TABLE IF EXISTS measurements');
        $this->addSql('DROP TABLE IF EXISTS waste');
        $this->addSql('DROP TABLE IF EXISTS waste_managers');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
