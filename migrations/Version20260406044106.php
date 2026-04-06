<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406044106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE recipient_organizations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:recipient_organization_id)',
              name VARCHAR(255) NOT NULL,
              type VARCHAR(255) NOT NULL,
              nif VARCHAR(50) NOT NULL,
              social_reason VARCHAR(255) NOT NULL,
              address LONGTEXT NOT NULL,
              operating_hours VARCHAR(255) NOT NULL,
              pickup_types JSON NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              contact_name VARCHAR(255) NOT NULL,
              contact_email VARCHAR(255) NOT NULL,
              contact_phone VARCHAR(50) NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE recipient_organizations');
    }
}
