<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910163712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_6BAEC7ECAEA34913 ON credit_purchases');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              credit_purchases
            ADD
              offset_tonnes DOUBLE PRECISION NOT NULL,
            ADD
              voided_tonnes DOUBLE PRECISION NOT NULL,
            ADD
              lot_number INT NOT NULL DEFAULT 1
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE credit_purchases SET lot_number = 1 WHERE lot_number = 0
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_credit_purchase_reference_lot ON credit_purchases (reference, lot_number)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_credit_purchase_reference_lot ON credit_purchases');
        $this->addSql('ALTER TABLE credit_purchases DROP offset_tonnes, DROP voided_tonnes, DROP lot_number');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6BAEC7ECAEA34913 ON credit_purchases (reference)');
    }
}
