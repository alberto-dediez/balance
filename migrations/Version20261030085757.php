<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030085757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprint_certificate_files (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_certificate_file_id)',
              certificate_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_certificate_id)',
              locale VARCHAR(5) NOT NULL,
              path VARCHAR(255) NOT NULL,
              UNIQUE INDEX uniq_pcf_certificate_file_locale (certificate_id, locale),
              INDEX IDX_879A572499223FFD (certificate_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_carbon_footprint_certificates (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_certificate_id)',
              property_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_carbon_footprint_id)',
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              year INT NOT NULL,
              code VARCHAR(20) NOT NULL,
              verification_token VARCHAR(64) NOT NULL,
              status VARCHAR(20) NOT NULL,
              locale VARCHAR(5) NOT NULL,
              property_name VARCHAR(255) NOT NULL,
              account_name VARCHAR(255) NOT NULL,
              stays INT NOT NULL,
              total_co2eq_kg DOUBLE PRECISION NOT NULL,
              co2eq_kg_per_stay DOUBLE PRECISION NOT NULL,
              scope_1_co2eq_kg DOUBLE PRECISION NOT NULL,
              scope_2_co2eq_kg DOUBLE PRECISION NOT NULL,
              scope_3_co2eq_kg DOUBLE PRECISION DEFAULT NULL,
              failure_reason LONGTEXT DEFAULT NULL,
              issued_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              generated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              failed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX idx_pcf_certificate_footprint (property_carbon_footprint_id),
              INDEX idx_pcf_certificate_account (account_id),
              UNIQUE INDEX uniq_pcf_certificate_code (code),
              UNIQUE INDEX uniq_pcf_certificate_verification_token (verification_token),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              property_carbon_footprint_certificate_files
            ADD
              CONSTRAINT FK_879A572499223FFD FOREIGN KEY (certificate_id) REFERENCES property_carbon_footprint_certificates (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_carbon_footprint_certificate_files DROP FOREIGN KEY FK_879A572499223FFD');
        $this->addSql('DROP TABLE property_carbon_footprint_certificate_files');
        $this->addSql('DROP TABLE property_carbon_footprint_certificates');
    }
}
