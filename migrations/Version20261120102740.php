<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261120102740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contact_requests (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:contact_request_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              origin VARCHAR(40) NOT NULL,
              origin_id VARCHAR(36) NOT NULL,
              origin_name VARCHAR(255) DEFAULT NULL,
              co2eq_kg DOUBLE PRECISION NOT NULL,
              contact_name VARCHAR(255) NOT NULL,
              contact_email VARCHAR(255) NOT NULL,
              contact_phone VARCHAR(40) DEFAULT NULL,
              contact_country VARCHAR(3) NOT NULL,
              contact_is_company TINYINT(1) NOT NULL,
              contact_company_name VARCHAR(255) DEFAULT NULL,
              contact_vat_number VARCHAR(40) DEFAULT NULL,
              comment LONGTEXT DEFAULT NULL,
              locale VARCHAR(5) NOT NULL,
              status VARCHAR(20) NOT NULL,
              requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              resolved_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              INDEX idx_contact_request_status (status, requested_at),
              INDEX idx_contact_request_account (account_id),
              UNIQUE INDEX uq_contact_request_origin (origin, origin_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE contact_requests');
    }
}
