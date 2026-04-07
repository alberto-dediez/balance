<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407111403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE packaging_property_stocks (
              property_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
              packaging_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:packaging_id)',
              stock INT NOT NULL,
              INDEX IDX_C3D99B794E7B3801 (packaging_id),
              PRIMARY KEY(property_id, packaging_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE packagings (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:packaging_id)',
              name VARCHAR(255) NOT NULL,
              packaging_type VARCHAR(255) NOT NULL,
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
              packaging_property_stocks
            ADD
              CONSTRAINT FK_C3D99B794E7B3801 FOREIGN KEY (packaging_id) REFERENCES packagings (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE packaging_property_stocks DROP FOREIGN KEY FK_C3D99B794E7B3801');
        $this->addSql('DROP TABLE packaging_property_stocks');
        $this->addSql('DROP TABLE packagings');
    }
}
