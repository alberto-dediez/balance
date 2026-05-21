<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521105031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE surpluses (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:surplus_id)',
              date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              service_category_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:service_category_id)',
              critical_point_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_id)',
              surplus_cause_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:surplus_cause_id)',
              food_type VARCHAR(255) NOT NULL,
              quantity DOUBLE PRECISION NOT NULL,
              unit VARCHAR(255) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              registered_by VARCHAR(36) NOT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE surpluses');
    }
}
