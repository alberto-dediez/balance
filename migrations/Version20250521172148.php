<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250521172148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE
          containers
        CHANGE
          purpose purpose VARCHAR(255) NOT NULL,
        CHANGE
          measurement_method measurement_method VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          purpose purpose VARCHAR(255) NOT NULL,
        CHANGE
          measurement_method measurement_method VARCHAR(255) NOT NULL');

        $this->addSql("UPDATE containers SET purpose = 'WASTE', measurement_method = 'VOLUME'");
        $this->addSql("UPDATE measurements SET purpose = 'WASTE', measurement_method = 'VOLUME'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE
          containers
        CHANGE
          purpose purpose LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
        CHANGE
          measurement_method measurement_method LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\'');
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          purpose purpose LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\',
        CHANGE
          measurement_method measurement_method LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\'');
    }
}
