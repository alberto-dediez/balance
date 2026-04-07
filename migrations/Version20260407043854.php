<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407043854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plan_properties (
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              prevention_plan_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_id)',
              INDEX IDX_839809F2C44547A6 (prevention_plan_id),
              PRIMARY KEY(property_id, prevention_plan_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prevention_plans (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:prevention_plan_id)',
              name VARCHAR(255) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              prevention_plan_properties
            ADD
              CONSTRAINT FK_839809F2C44547A6 FOREIGN KEY (prevention_plan_id) REFERENCES prevention_plans (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prevention_plan_properties DROP FOREIGN KEY FK_839809F2C44547A6');
        $this->addSql('DROP TABLE prevention_plan_properties');
        $this->addSql('DROP TABLE prevention_plans');
    }
}
