<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260619092753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              delivery_logs
            ADD
              delivered TINYINT(1) NOT NULL,
            ADD
              delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              signature_request_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:signature_request_id)',
            ADD
              pickup_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              recipient_organizations
            ADD
              contact_preferred_locale VARCHAR(255) DEFAULT NULL COMMENT '(DC2Type:locale)',
            ADD
              contact_preferred_notification_channel VARCHAR(255) NOT NULL
        SQL);
        $this->addSql("UPDATE recipient_organizations SET contact_preferred_notification_channel = 'Email'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              delivery_logs
            DROP
              delivered,
            DROP
              delivered_at,
            DROP
              signature_request_id,
            DROP
              pickup_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              recipient_organizations
            DROP
              contact_preferred_locale,
            DROP
              contact_preferred_notification_channel
        SQL);
    }
}
