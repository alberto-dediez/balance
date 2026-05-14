<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514043530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE incidence_evidences (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:incidence_evidence_id)',
              incidence_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:incidence_id)',
              path VARCHAR(500) NOT NULL,
              original_name VARCHAR(255) NOT NULL,
              mime_type VARCHAR(100) NOT NULL,
              uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_B7A17F7FF22710E3 (incidence_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              incidence_evidences
            ADD
              CONSTRAINT FK_B7A17F7FF22710E3 FOREIGN KEY (incidence_id) REFERENCES incidences (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE incidence_evidences DROP FOREIGN KEY FK_B7A17F7FF22710E3');
        $this->addSql('DROP TABLE incidence_evidences');
    }
}
