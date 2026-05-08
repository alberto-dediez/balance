<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507174527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_version_properties (
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              prevention_plan_version_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_version_id)',
              INDEX IDX_E8AF22324FE71F30 (prevention_plan_version_id),
              PRIMARY KEY(
                property_id, prevention_plan_version_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_version_properties
            ADD
              CONSTRAINT FK_E8AF22324FE71F30 FOREIGN KEY (prevention_plan_version_id) REFERENCES prevention_plan_versions (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO prevention_plan_version_properties (prevention_plan_version_id, property_id)
              SELECT ppv.id, ppp.property_id
              FROM   prevention_plan_properties ppp
              JOIN   prevention_plans pp  ON pp.id  = ppp.prevention_plan_id
              JOIN   prevention_plan_versions ppv
                       ON ppv.prevention_plan_id = pp.id
                      AND ppv.version_number     = pp.current_version;
        SQL);

        $this->addSql('ALTER TABLE prevention_plan_properties DROP FOREIGN KEY FK_839809F2C44547A6');
        $this->addSql('DROP TABLE prevention_plan_properties');
        $this->addSql('ALTER TABLE prevention_plans ADD use_all_company_properties TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_properties (
              property_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT '(DC2Type:property_id)',
              prevention_plan_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT '(DC2Type:prevention_plan_id)',
              INDEX IDX_839809F2C44547A6 (prevention_plan_id),
              PRIMARY KEY(property_id, prevention_plan_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_properties
            ADD
              CONSTRAINT FK_839809F2C44547A6 FOREIGN KEY (prevention_plan_id) REFERENCES prevention_plans (id) ON
            UPDATE
              NO ACTION ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
           INSERT INTO prevention_plan_properties (prevention_plan_id, property_id)
          SELECT pp.id, pvp.property_id
          FROM   prevention_plan_version_properties pvp
          JOIN   prevention_plan_versions ppv
                   ON ppv.id = pvp.prevention_plan_version_id
          JOIN   prevention_plans pp
                   ON pp.id             = ppv.prevention_plan_id
                  AND pp.current_version = ppv.version_number
        SQL);

        $this->addSql('ALTER TABLE prevention_plan_version_properties DROP FOREIGN KEY FK_E8AF22324FE71F30');
        $this->addSql('DROP TABLE prevention_plan_version_properties');
        $this->addSql('ALTER TABLE prevention_plans DROP use_all_company_properties');
    }
}
