<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260820054727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE branding (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:branding_id)',
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              theme_slug VARCHAR(64) NOT NULL,
              logo_media_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)',
              `primary` VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              secondary VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              success VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              info VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              warning VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              danger VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              light VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              dark VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              body_color VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              body_bg VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              primary_hover VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              primary_subtle VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              secondary_hover VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              secondary_subtle VARCHAR(7) DEFAULT NULL COMMENT '(DC2Type:hex_color)',
              web_font_family VARCHAR(100) DEFAULT NULL COMMENT '(DC2Type:font_family_name)',
              web_font_weights VARCHAR(50) DEFAULT NULL,
              pdf_font_family VARCHAR(50) NOT NULL,
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uq_branding_account_id (account_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE branding');
    }
}
