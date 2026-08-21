<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821052419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_purchases ADD notified_thresholds JSON NOT NULL');
        $this->addSql('UPDATE credit_purchases SET notified_thresholds = \'[]\'');
        $this->addSql("UPDATE contribution_certificates SET credit_lots = JSON_ARRAY() WHERE JSON_TYPE(credit_lots) = 'NULL'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_purchases DROP notified_thresholds');
    }
}
