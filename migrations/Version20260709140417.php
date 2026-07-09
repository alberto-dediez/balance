<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260709140417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE provider_contributions (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:provider_contribution_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              provider_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:provider_id)',
              token VARCHAR(64) NOT NULL,
              status VARCHAR(20) NOT NULL,
              step_status VARCHAR(30) NOT NULL,
              answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ignored_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              company_name VARCHAR(255) DEFAULT NULL,
              company_type VARCHAR(255) DEFAULT NULL,
              service VARCHAR(255) DEFAULT NULL,
              workers_assembly INT DEFAULT NULL,
              workers_celebration INT DEFAULT NULL,
              workers_breakdown INT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_C3BAA7C35F37A13B (token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_carbon_footprint_activities
            ADD
              provider_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:provider_id)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              providers
            ADD
              user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
            ADD
              email VARCHAR(255) NOT NULL,
            ADD
              preferred_locale VARCHAR(255) DEFAULT NULL COMMENT '(DC2Type:locale)',
            ADD
              contact_name VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE provider_contributions');
        $this->addSql('ALTER TABLE event_carbon_footprint_activities DROP provider_id');
        $this->addSql('ALTER TABLE providers DROP user_id, DROP email, DROP preferred_locale, DROP contact_name');
    }
}
