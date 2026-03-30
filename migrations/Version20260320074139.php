<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320074139 extends AbstractMigration
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
              waste_translation
            ADD
              CONSTRAINT FK_7CDF8BABFA6A22C2 FOREIGN KEY (waste_id) REFERENCES waste (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_7CDF8BABFA6A22C2 ON waste_translation (waste_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE waste_translation DROP FOREIGN KEY FK_7CDF8BABFA6A22C2');
        $this->addSql('DROP INDEX IDX_7CDF8BABFA6A22C2 ON waste_translation');
    }
}
