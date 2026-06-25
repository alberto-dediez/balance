<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625162904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE material_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:material_emission_factor_id)',
              material_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:material_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_89E20118E308AC6F (material_id),
              UNIQUE INDEX uq_material_emission_factor_year (material_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              material_emission_factor
            ADD
              CONSTRAINT FK_89E20118E308AC6F FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE material_emission_factor DROP FOREIGN KEY FK_89E20118E308AC6F');
        $this->addSql('DROP TABLE material_emission_factor');
    }
}
