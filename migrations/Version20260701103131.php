<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701103131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE emission_factor_group_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_9451D9B7E23412D6 (emission_factor_group_id),
              PRIMARY KEY(
                locale, emission_factor_group_id
              )
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE emission_factor_groups (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              code VARCHAR(50) NOT NULL,
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              gwp_co2 DOUBLE PRECISION NOT NULL,
              gwp_ch4 DOUBLE PRECISION NOT NULL,
              gwp_n2_o DOUBLE PRECISION NOT NULL,
              UNIQUE INDEX uq_emission_factor_group_code (code),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emission_factor_group_translation
            ADD
              CONSTRAINT FK_9451D9B7E23412D6 FOREIGN KEY (emission_factor_group_id) REFERENCES emission_factor_groups (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emission_factor_group_translation DROP FOREIGN KEY FK_9451D9B7E23412D6');
        $this->addSql('DROP TABLE emission_factor_group_translation');
        $this->addSql('DROP TABLE emission_factor_groups');
    }
}
