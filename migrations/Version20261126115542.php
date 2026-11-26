<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126115542 extends AbstractMigration
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
              credit_purchases
            ADD
              requested_amount DOUBLE PRECISION DEFAULT NULL,
            ADD
              requested_vat_rate DOUBLE PRECISION DEFAULT NULL,
            ADD
              requested_vat_amount DOUBLE PRECISION DEFAULT NULL,
            ADD
              requested_vat_reason VARCHAR(30) DEFAULT NULL,
            ADD
              billing_legal_name VARCHAR(255) DEFAULT NULL,
            ADD
              billing_tax_id VARCHAR(50) DEFAULT NULL,
            ADD
              billing_street VARCHAR(512) DEFAULT NULL,
            ADD
              billing_postal_code VARCHAR(20) DEFAULT NULL,
            ADD
              billing_city VARCHAR(255) DEFAULT NULL,
            ADD
              billing_country VARCHAR(10) DEFAULT NULL,
            ADD
              billing_email VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              credit_purchases
            DROP
              requested_amount,
            DROP
              requested_vat_rate,
            DROP
              requested_vat_amount,
            DROP
              requested_vat_reason,
            DROP
              billing_legal_name,
            DROP
              billing_tax_id,
            DROP
              billing_street,
            DROP
              billing_postal_code,
            DROP
              billing_city,
            DROP
              billing_country,
            DROP
              billing_email
        SQL);
    }
}
