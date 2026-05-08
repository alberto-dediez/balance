<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508120630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `primary` ON critical_point_action_link');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_action_link
            ADD
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_action_link_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE critical_point_action_link SET id = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_critical_point_action_link ON critical_point_action_link (critical_point_id, action_id)
        SQL);
        $this->addSql('ALTER TABLE critical_point_action_link ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX `primary` ON critical_point_service_categories');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_service_categories
            ADD
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_service_category_link_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE critical_point_service_categories SET id = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_critical_point_service_category_link ON critical_point_service_categories (
              critical_point_id, service_category_id
            )
        SQL);
        $this->addSql('ALTER TABLE critical_point_service_categories ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX `primary` ON critical_point_surplus_causes');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_surplus_causes
            ADD
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_surplus_cause_link_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE critical_point_surplus_causes SET id = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_critical_point_surplus_cause_link ON critical_point_surplus_causes (
              critical_point_id, surplus_cause_id
            )
        SQL);
        $this->addSql('ALTER TABLE critical_point_surplus_causes ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX `primary` ON packaging_property_stocks');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              packaging_property_stocks
            ADD
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:packaging_property_stock_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE packaging_property_stocks SET id = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_packaging_property_stock ON packaging_property_stocks (packaging_id, property_id)
        SQL);
        $this->addSql('ALTER TABLE packaging_property_stocks ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_critical_point_action_link ON critical_point_action_link');
        $this->addSql('DROP INDEX `PRIMARY` ON critical_point_action_link');
        $this->addSql('ALTER TABLE critical_point_action_link DROP id');
        $this->addSql('ALTER TABLE critical_point_action_link ADD PRIMARY KEY (action_id, critical_point_id)');
        $this->addSql('DROP INDEX uq_critical_point_service_category_link ON critical_point_service_categories');
        $this->addSql('DROP INDEX `PRIMARY` ON critical_point_service_categories');
        $this->addSql('ALTER TABLE critical_point_service_categories DROP id');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_service_categories
            ADD
              PRIMARY KEY (
                service_category_id, critical_point_id
              )
        SQL);
        $this->addSql('DROP INDEX uq_critical_point_surplus_cause_link ON critical_point_surplus_causes');
        $this->addSql('DROP INDEX `PRIMARY` ON critical_point_surplus_causes');
        $this->addSql('ALTER TABLE critical_point_surplus_causes DROP id');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_surplus_causes
            ADD
              PRIMARY KEY (
                surplus_cause_id, critical_point_id
              )
        SQL);
        $this->addSql('DROP INDEX uq_packaging_property_stock ON packaging_property_stocks');
        $this->addSql('DROP INDEX `PRIMARY` ON packaging_property_stocks');
        $this->addSql('ALTER TABLE packaging_property_stocks DROP id');
        $this->addSql('ALTER TABLE packaging_property_stocks ADD PRIMARY KEY (property_id, packaging_id)');
    }
}
