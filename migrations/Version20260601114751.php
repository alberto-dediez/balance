<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601114751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE alert_configuration_items (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:alert_configuration_item_id)',
              alert_configuration_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:alert_configuration_id)',
              type VARCHAR(255) NOT NULL,
              enabled TINYINT(1) NOT NULL,
              parameters JSON NOT NULL,
              INDEX IDX_4BB2623B701B6C9A (alert_configuration_id),
              UNIQUE INDEX uq_alert_config_item_type (alert_configuration_id, type),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE alert_configurations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:alert_configuration_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uq_alert_config_account (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE alerts (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:alert_id)',
              type VARCHAR(255) NOT NULL,
              status VARCHAR(255) NOT NULL,
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              waste_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:waste_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              detected_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              period_from DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              period_to DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              value DOUBLE PRECISION DEFAULT NULL,
              threshold DOUBLE PRECISION DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              alert_configuration_items
            ADD
              CONSTRAINT FK_4BB2623B701B6C9A FOREIGN KEY (alert_configuration_id) REFERENCES alert_configurations (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_configuration_items DROP FOREIGN KEY FK_4BB2623B701B6C9A');
        $this->addSql('DROP TABLE alert_configuration_items');
        $this->addSql('DROP TABLE alert_configurations');
        $this->addSql('DROP TABLE alerts');
    }
}
