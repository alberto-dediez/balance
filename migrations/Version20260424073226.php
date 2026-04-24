<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424073226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE employees (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_id)',
              name VARCHAR(255) NOT NULL,
              email VARCHAR(255) NOT NULL,
              phone VARCHAR(20) DEFAULT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              street VARCHAR(512) DEFAULT NULL,
              postal_code VARCHAR(20) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              country VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE employees');
    }
}
