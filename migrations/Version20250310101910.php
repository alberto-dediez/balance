<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310101910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE properties (
          id VARCHAR(36) NOT NULL,
          account_id VARCHAR(36) DEFAULT NULL,
          name VARCHAR(255) NOT NULL,
          removed TINYINT(1) DEFAULT NULL,
          events LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\',
          registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          INDEX IDX_87C331C79B6B5FBA (account_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          properties
        ADD
          CONSTRAINT FK_87C331C79B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_87C331C79B6B5FBA');
        $this->addSql('DROP TABLE properties');
    }
}
