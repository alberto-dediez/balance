<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526123007 extends AbstractMigration
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
              surpluses
            ADD
              human_donation_donation_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              human_donation_batch VARCHAR(255) DEFAULT NULL,
            ADD
              human_donation_recipient_organization_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:recipient_organization_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              surpluses
            DROP
              human_donation_donation_date,
            DROP
              human_donation_batch,
            DROP
              human_donation_recipient_organization_id
        SQL);
    }
}
