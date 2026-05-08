<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508114415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `primary` ON critical_point_reason_link');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              critical_point_reason_link
            ADD
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:critical_point_reason_link_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE critical_point_reason_link SET id = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_critical_point_reason_link ON critical_point_reason_link (critical_point_id, reason_id)
        SQL);
        $this->addSql('ALTER TABLE critical_point_reason_link ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_critical_point_reason_link ON critical_point_reason_link');
        $this->addSql('DROP INDEX `PRIMARY` ON critical_point_reason_link');
        $this->addSql('ALTER TABLE critical_point_reason_link DROP id');
        $this->addSql('ALTER TABLE critical_point_reason_link ADD PRIMARY KEY (reason_id, critical_point_id)');
    }
}
