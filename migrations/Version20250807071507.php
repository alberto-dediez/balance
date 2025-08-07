<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250807071507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brands CHANGE id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:brand_id)\'');
        $this->addSql('ALTER TABLE categories CHANGE id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:category_id)\'');
        $this->addSql('ALTER TABLE
          containers
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:container_id)\',
        CHANGE
          property_id property_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:property_id)\',
        CHANGE
          waste_id waste_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_id)\',
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\',
        CHANGE
          waste_manager_id waste_manager_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_manager_id)\'');
        $this->addSql('ALTER TABLE countries CHANGE id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:country_id)\'');
        $this->addSql('ALTER TABLE
          destinations
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:destination_id)\',
        CHANGE
          country_id country_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:country_id)\'');
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:measurement_id)\',
        CHANGE
          container_id container_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:container_id)\',
        CHANGE
          property_id property_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:property_id)\',
        CHANGE
          waste_id waste_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:waste_id)\',
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\'');
        $this->addSql('ALTER TABLE
          properties
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:property_id)\',
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\',
        CHANGE
          category_id category_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:category_id)\',
        CHANGE
          brand_id brand_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:brand_id)\',
        CHANGE
          destination_id destination_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:destination_id)\',
        CHANGE
          country_id country_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:country_id)\'');
        $this->addSql('ALTER TABLE waste CHANGE id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:waste_id)\'');
        $this->addSql('ALTER TABLE
          waste_managers
        CHANGE
          id id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:waste_manager_id)\',
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL COMMENT \'(DC2Type:account_id)\'');
        $this->addSql('ALTER TABLE
          waste_translation
        CHANGE
          waste_id waste_id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:waste_id)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brands CHANGE id id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE categories CHANGE id id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE
          containers
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          property_id property_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          waste_id waste_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          waste_manager_id waste_manager_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE countries CHANGE id id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE
          destinations
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          country_id country_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE
          measurements
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          container_id container_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          property_id property_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          waste_id waste_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE
          properties
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          category_id category_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          brand_id brand_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          destination_id destination_id VARCHAR(36) DEFAULT NULL,
        CHANGE
          country_id country_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE waste CHANGE id id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE
          waste_managers
        CHANGE
          id id VARCHAR(36) NOT NULL,
        CHANGE
          account_id account_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE waste_translation CHANGE waste_id waste_id VARCHAR(36) NOT NULL');
    }
}
