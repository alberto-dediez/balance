<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401123937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE action CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              action_translation
            ADD
              CONSTRAINT FK_CFBBCCAB9D32F035 FOREIGN KEY (action_id) REFERENCES action (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_CFBBCCAB9D32F035 ON action_translation (action_id)');
        $this->addSql('ALTER TABLE service_category CHANGE removed removed TINYINT(1) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              service_category_translation
            ADD
              CONSTRAINT FK_13E9EA90DEDCBB4E FOREIGN KEY (service_category_id) REFERENCES service_category (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_13E9EA90DEDCBB4E ON service_category_translation (service_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE action_translation DROP FOREIGN KEY FK_CFBBCCAB9D32F035');
        $this->addSql('DROP INDEX IDX_CFBBCCAB9D32F035 ON action_translation');
        $this->addSql('ALTER TABLE service_category CHANGE removed removed TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_category_translation DROP FOREIGN KEY FK_13E9EA90DEDCBB4E');
        $this->addSql('DROP INDEX IDX_13E9EA90DEDCBB4E ON service_category_translation');
    }
}
