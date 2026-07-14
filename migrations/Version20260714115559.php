<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260714115559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_carbon_footprint_report_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              event_carbon_footprint_report_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_report_id)',
              file_path VARCHAR(255) NOT NULL,
              INDEX IDX_37BC76D6A465209A (
                event_carbon_footprint_report_id
              ),
              PRIMARY KEY(
                locale, event_carbon_footprint_report_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_carbon_footprint_reports (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_report_id)',
              event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              requested_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              status VARCHAR(255) NOT NULL,
              error_message LONGTEXT DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              generated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX UNIQ_B8723B19B6931EEA (event_carbon_footprint_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_carbon_footprint_report_translation
            ADD
              CONSTRAINT FK_37BC76D6A465209A FOREIGN KEY (
                event_carbon_footprint_report_id
              ) REFERENCES event_carbon_footprint_reports (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_carbon_footprint_report_translation DROP FOREIGN KEY FK_37BC76D6A465209A');
        $this->addSql('DROP TABLE event_carbon_footprint_report_translation');
        $this->addSql('DROP TABLE event_carbon_footprint_reports');
    }
}
