<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810152434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_purchases DROP amount');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_contributions
            CHANGE
              credit_cost_per_tonne cost_per_tonne DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE projects ADD cost_per_tonne DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_purchases ADD amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_contributions
            CHANGE
              cost_per_tonne credit_cost_per_tonne DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE projects DROP cost_per_tonne');
    }
}
