<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Carbon footprint report: generic subject (event, property, company), reviewable texts as sections and account report permissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE account
              ADD report_can_edit_texts TINYINT(1) NOT NULL DEFAULT 0,
              ADD report_auto_review TINYINT(1) NOT NULL DEFAULT 1
            SQL);
        $this->addSql('ALTER TABLE account ALTER report_can_edit_texts DROP DEFAULT');
        $this->addSql('ALTER TABLE account ALTER report_auto_review DROP DEFAULT');

        $this->addSql('ALTER TABLE event_carbon_footprint_report_translation DROP FOREIGN KEY FK_37BC76D6A465209A');
        $this->addSql('ALTER TABLE event_carbon_footprint_reports DROP INDEX UNIQ_B8723B19B6931EEA');

        $this->addSql('RENAME TABLE event_carbon_footprint_reports TO carbon_footprint_reports');
        $this->addSql('RENAME TABLE event_carbon_footprint_report_translation TO carbon_footprint_report_translation');

        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_reports
              CHANGE id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:carbon_footprint_report_id)',
              CHANGE event_carbon_footprint_id carbon_footprint_id VARCHAR(36) NOT NULL,
              ADD subject_type VARCHAR(20) NOT NULL DEFAULT 'event' AFTER id,
              ADD base_locale VARCHAR(5) DEFAULT NULL COMMENT '(DC2Type:locale)' AFTER status,
              ADD submitted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ADD approved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ADD auto_approved TINYINT(1) NOT NULL DEFAULT 0,
              ADD reviewed_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              ADD reviewed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              ADD review_comment LONGTEXT DEFAULT NULL
            SQL);
        $this->addSql('ALTER TABLE carbon_footprint_reports ALTER subject_type DROP DEFAULT');
        $this->addSql('ALTER TABLE carbon_footprint_reports ALTER auto_approved DROP DEFAULT');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_CARBON_FOOTPRINT_REPORT_SUBJECT ON carbon_footprint_reports (subject_type, carbon_footprint_id)');
        $this->addSql('CREATE INDEX IDX_CARBON_FOOTPRINT_REPORT_STATUS ON carbon_footprint_reports (status)');

        $this->addSql(<<<'SQL'
            UPDATE carbon_footprint_reports
            SET status = 'failed',
              error_message = 'Generación interrumpida por la actualización de la plataforma. Reintenta la generación del informe.'
            WHERE status = 'pending'
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_report_translation
              CHANGE event_carbon_footprint_report_id carbon_footprint_report_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:carbon_footprint_report_id)'
            SQL);
        $this->addSql('ALTER TABLE carbon_footprint_report_translation RENAME INDEX IDX_37BC76D6A465209A TO IDX_CARBON_FOOTPRINT_REPORT_TRANSLATION_REPORT');
        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_report_translation
              ADD CONSTRAINT FK_CARBON_FOOTPRINT_REPORT_TRANSLATION_REPORT FOREIGN KEY (carbon_footprint_report_id)
                REFERENCES carbon_footprint_reports (id) ON DELETE CASCADE
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE carbon_footprint_report_sections (
              carbon_footprint_report_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:carbon_footprint_report_id)',
              section_key VARCHAR(50) NOT NULL,
              content LONGTEXT NOT NULL,
              position INT NOT NULL,
              INDEX IDX_CARBON_FOOTPRINT_REPORT_SECTIONS_REPORT (carbon_footprint_report_id),
              PRIMARY KEY (section_key, carbon_footprint_report_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_report_sections
              ADD CONSTRAINT FK_CARBON_FOOTPRINT_REPORT_SECTIONS_REPORT FOREIGN KEY (carbon_footprint_report_id)
                REFERENCES carbon_footprint_reports (id) ON DELETE CASCADE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE carbon_footprint_report_sections DROP FOREIGN KEY FK_CARBON_FOOTPRINT_REPORT_SECTIONS_REPORT');
        $this->addSql('DROP TABLE carbon_footprint_report_sections');

        $this->addSql("DELETE FROM carbon_footprint_reports WHERE subject_type <> 'event'");
        $this->addSql("UPDATE carbon_footprint_reports SET status = 'pending' WHERE status IN ('generating_texts', 'draft', 'pending_review', 'generating_pdf')");
        $this->addSql("UPDATE carbon_footprint_reports SET status = 'failed' WHERE status = 'rejected'");

        $this->addSql('ALTER TABLE carbon_footprint_report_translation DROP FOREIGN KEY FK_CARBON_FOOTPRINT_REPORT_TRANSLATION_REPORT');
        $this->addSql('ALTER TABLE carbon_footprint_report_translation RENAME INDEX IDX_CARBON_FOOTPRINT_REPORT_TRANSLATION_REPORT TO IDX_37BC76D6A465209A');
        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_report_translation
              CHANGE carbon_footprint_report_id event_carbon_footprint_report_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_report_id)'
            SQL);

        $this->addSql('DROP INDEX UNIQ_CARBON_FOOTPRINT_REPORT_SUBJECT ON carbon_footprint_reports');
        $this->addSql('DROP INDEX IDX_CARBON_FOOTPRINT_REPORT_STATUS ON carbon_footprint_reports');
        $this->addSql(<<<'SQL'
            ALTER TABLE carbon_footprint_reports
              DROP subject_type,
              DROP base_locale,
              DROP submitted_at,
              DROP approved_at,
              DROP auto_approved,
              DROP reviewed_by,
              DROP reviewed_at,
              DROP review_comment,
              CHANGE carbon_footprint_id event_carbon_footprint_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_id)',
              CHANGE id id VARCHAR(36) NOT NULL COMMENT '(DC2Type:event_carbon_footprint_report_id)'
            SQL);

        $this->addSql('RENAME TABLE carbon_footprint_report_translation TO event_carbon_footprint_report_translation');
        $this->addSql('RENAME TABLE carbon_footprint_reports TO event_carbon_footprint_reports');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8723B19B6931EEA ON event_carbon_footprint_reports (event_carbon_footprint_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE event_carbon_footprint_report_translation
              ADD CONSTRAINT FK_37BC76D6A465209A FOREIGN KEY (event_carbon_footprint_report_id)
                REFERENCES event_carbon_footprint_reports (id) ON DELETE CASCADE
            SQL);

        $this->addSql('ALTER TABLE account DROP report_can_edit_texts, DROP report_auto_review');
    }
}
