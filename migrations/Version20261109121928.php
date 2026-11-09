<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109121928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add origin to credit_purchases (requested | assigned | received), backfilling received lots (lot_number > 1)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE credit_purchases ADD origin VARCHAR(20) NOT NULL DEFAULT 'requested'");
        $this->addSql("UPDATE credit_purchases SET origin = 'received' WHERE lot_number > 1");
        $this->addSql('ALTER TABLE credit_purchases ALTER origin DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_purchases DROP origin');
    }
}
