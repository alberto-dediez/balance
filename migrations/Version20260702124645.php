<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702124645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              events
            ADD
              assembly_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              assembly_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              breakdown_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              breakdown_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            DROP
              dismantling_start_date,
            DROP
              dismantling_end_date,
            DROP
              setup_start_date,
            DROP
              setup_end_date
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              events
            ADD
              dismantling_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              dismantling_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              setup_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              setup_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            DROP
              assembly_start_date,
            DROP
              assembly_end_date,
            DROP
              breakdown_start_date,
            DROP
              breakdown_end_date
        SQL);
    }
}
