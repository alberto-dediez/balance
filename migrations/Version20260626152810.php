<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626152810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE road_transport_emission_factor (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_emission_factor_id)',
              road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)',
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              co2eq NUMERIC(20, 8) NOT NULL,
              co2 NUMERIC(20, 8) NOT NULL,
              ch4 NUMERIC(20, 8) NOT NULL,
              n2o NUMERIC(20, 8) NOT NULL,
              year INT NOT NULL,
              unit VARCHAR(255) NOT NULL,
              source VARCHAR(255) DEFAULT NULL,
              INDEX IDX_3478DE3E6C5E3E99 (road_transport_id),
              UNIQUE INDEX uq_road_transport_emission_factor_year (road_transport_id, year),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE road_transport_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              road_transport_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)',
              name VARCHAR(255) NOT NULL,
              INDEX IDX_30DF047B6C5E3E99 (road_transport_id),
              PRIMARY KEY(locale, road_transport_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE road_transports (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:road_transport_id)',
              name VARCHAR(255) NOT NULL,
              unit VARCHAR(255) NOT NULL,
              vehicle VARCHAR(255) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_emission_factor
            ADD
              CONSTRAINT FK_3478DE3E6C5E3E99 FOREIGN KEY (road_transport_id) REFERENCES road_transports (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              road_transport_translation
            ADD
              CONSTRAINT FK_30DF047B6C5E3E99 FOREIGN KEY (road_transport_id) REFERENCES road_transports (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE road_transport_emission_factor DROP FOREIGN KEY FK_3478DE3E6C5E3E99');
        $this->addSql('ALTER TABLE road_transport_translation DROP FOREIGN KEY FK_30DF047B6C5E3E99');
        $this->addSql('DROP TABLE road_transport_emission_factor');
        $this->addSql('DROP TABLE road_transport_translation');
        $this->addSql('DROP TABLE road_transports');
    }
}
