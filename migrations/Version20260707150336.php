<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707150336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_items DROP unit_weight');
        $this->addSql('ALTER TABLE materials DROP unit_weight');
        $this->addSql('ALTER TABLE merchandising_items DROP unit_weight');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_items ADD unit_weight NUMERIC(20, 8) DEFAULT NULL');
        $this->addSql('ALTER TABLE materials ADD unit_weight NUMERIC(20, 8) DEFAULT NULL');
        $this->addSql('ALTER TABLE merchandising_items ADD unit_weight NUMERIC(20, 8) DEFAULT NULL');
    }
}
