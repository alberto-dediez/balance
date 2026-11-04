<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104151150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE distributor_assignments (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:distributor_assignment_id)',
              distributor_account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              property_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:property_id)',
              registered_by VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_distributor_assignments_distributor (distributor_account_id),
              INDEX IDX_distributor_assignments_account (account_id),
              INDEX IDX_distributor_assignments_property (property_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE account ADD distributor TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE distributor_assignments');
        $this->addSql('ALTER TABLE account DROP distributor');
    }
}
