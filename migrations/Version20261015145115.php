<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015145115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE use_profile_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              use_profile_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:use_profile_id)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE use_profile_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:use_profile_emission_factor_id)',
              use_profile_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:use_profile_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              emission_factor_group_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:emission_factor_group_id)',
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX uq_use_profile_emission_factor_year (
                use_profile_id, year, emission_factor_group_id
              ),
              INDEX IDX_74E3A9B6CC8EFC91 (use_profile_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE use_profile_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              use_profile_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:use_profile_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_15BF482CC8EFC91 (use_profile_id),
              PRIMARY KEY(locale, use_profile_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE use_profiles (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:use_profile_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              use_profile_activities
            ADD
              CONSTRAINT FK_578F7358BF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              use_profile_emission_factor
            ADD
              CONSTRAINT FK_74E3A9B6CC8EFC91 FOREIGN KEY (use_profile_id) REFERENCES use_profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              use_profile_translation
            ADD
              CONSTRAINT FK_15BF482CC8EFC91 FOREIGN KEY (use_profile_id) REFERENCES use_profiles (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE use_profile_activities DROP FOREIGN KEY FK_578F7358BF396750');
        $this->addSql('ALTER TABLE use_profile_emission_factor DROP FOREIGN KEY FK_74E3A9B6CC8EFC91');
        $this->addSql('ALTER TABLE use_profile_translation DROP FOREIGN KEY FK_15BF482CC8EFC91');
        $this->addSql('DROP TABLE use_profile_activities');
        $this->addSql('DROP TABLE use_profile_emission_factor');
        $this->addSql('DROP TABLE use_profile_translation');
        $this->addSql('DROP TABLE use_profiles');
    }
}
