<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260608115703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_alert_config_item_type ON alert_configuration_items');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              alert_configuration_items
            ADD
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
            ADD
              waste_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:waste_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_configuration_items DROP property_id, DROP waste_id');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_alert_config_item_type ON alert_configuration_items (alert_configuration_id, type)
        SQL);
    }
}
