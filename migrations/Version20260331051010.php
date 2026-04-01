<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331051010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE containers SET removed = 0 WHERE removed IS NULL');
        $this->addSql('UPDATE countries SET removed = 0 WHERE removed IS NULL');
        $this->addSql('UPDATE destinations SET removed = 0 WHERE removed IS NULL');
        $this->addSql('ALTER TABLE containers CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE countries CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE destinations CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql('UPDATE importation SET status = \'Started\' WHERE status IS NULL');
        $this->addSql('UPDATE importation SET default_values = \'a:0:{}\' WHERE default_values IS NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              importation
            CHANGE
              default_values default_values LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
            CHANGE
              status status VARCHAR(255) NOT NULL
        SQL);
        $this->addSql('UPDATE waste SET removed = 0 WHERE removed IS NULL');
        $this->addSql('ALTER TABLE waste CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql('UPDATE waste_managers SET removed = 0 WHERE removed IS NULL');
        $this->addSql('UPDATE waste_managers SET is_public = 0 WHERE is_public IS NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_managers
            CHANGE
              is_public is_public TINYINT(1) NOT NULL,
            CHANGE
              removed removed TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE containers CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE countries CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE destinations CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              importation
            CHANGE
              status status VARCHAR(255) DEFAULT NULL,
            CHANGE
              default_values default_values LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'
        SQL);
        $this->addSql('ALTER TABLE waste CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              waste_managers
            CHANGE
              is_public is_public TINYINT(1) DEFAULT NULL,
            CHANGE
              removed removed TINYINT(1) DEFAULT NULL
        SQL);
    }
}
