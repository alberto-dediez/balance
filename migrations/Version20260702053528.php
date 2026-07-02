<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702053528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_emission_factor_group_code ON emission_factor_groups');
        $this->addSql('ALTER TABLE emission_factor_groups ADD is_default TINYINT(1) NOT NULL, DROP code');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emission_factor_groups ADD code VARCHAR(50) NOT NULL, DROP is_default');
        $this->addSql('CREATE UNIQUE INDEX uq_emission_factor_group_code ON emission_factor_groups (code)');
    }
}
