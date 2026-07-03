<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703062043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE importation_profile (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:importation_profile_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              type VARCHAR(255) NOT NULL,
              fields_by_column LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
              translations_by_column LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
              default_values LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uniq_importation_profile_account_type (account_id, type),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE importation_profile');
    }
}
