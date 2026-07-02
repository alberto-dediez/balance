<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702101850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_item_activities CHANGE `condition` item_condition VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE material_activities CHANGE `condition` item_condition VARCHAR(255) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_activities
            CHANGE
              `condition` item_condition VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_item_activities CHANGE item_condition `condition` VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE material_activities CHANGE item_condition `condition` VARCHAR(255) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              merchandising_item_activities
            CHANGE
              item_condition `condition` VARCHAR(255) NOT NULL
        SQL);
    }
}
