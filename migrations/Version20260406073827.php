<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406073827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE delivery_logs (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:delivery_log_id)',
              date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              quantity DOUBLE PRECISION NOT NULL,
              unit VARCHAR(255) NOT NULL,
              product_type VARCHAR(255) NOT NULL,
              batch VARCHAR(255) NOT NULL,
              recipient_organization_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:recipient_organization_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE delivery_logs');
    }
}
