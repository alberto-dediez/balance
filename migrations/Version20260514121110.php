<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514121110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE protocol_accesses (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:protocol_access_id)',
              protocol_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:protocol_id)',
              employee_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              accessed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE protocol_accesses');
    }
}
