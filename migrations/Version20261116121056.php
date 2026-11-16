<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116121056 extends AbstractMigration
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
              project_contributions
            ADD
              credit_account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE project_contributions SET credit_account_id = account_id;
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_contributions
            MODIFY
              credit_account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_contribution_credit_balance ON project_contributions (
              credit_account_id, project_id, status
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_project_contribution_credit_balance ON project_contributions');
        $this->addSql('ALTER TABLE project_contributions DROP credit_account_id');
    }
}
