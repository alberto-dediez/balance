<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527124945 extends AbstractMigration
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
              surpluses
            ADD
              animal_feed_waste_manager_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:food_waste_manager_id)',
            ADD
              composting_waste_manager_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:food_waste_manager_id)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE surpluses DROP animal_feed_waste_manager_id, DROP composting_waste_manager_id');
    }
}
