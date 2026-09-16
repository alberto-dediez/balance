<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915154335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE refrigerant_gas_activities (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:activity_id)',
              refrigerant_gas_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:refrigerant_gas_id)',
              source VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              refrigerant_gas_activities
            ADD
              CONSTRAINT FK_5C3068DABF396750 FOREIGN KEY (id) REFERENCES activities (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refrigerant_gas_activities DROP FOREIGN KEY FK_5C3068DABF396750');
        $this->addSql('DROP TABLE refrigerant_gas_activities');
    }
}
