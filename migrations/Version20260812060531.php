<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812060531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE public_landing_blocks (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:public_landing_block_id)',
              public_landing_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:public_landing_id)',
              position INT NOT NULL,
              image VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)',
              title VARCHAR(255) NOT NULL,
              text LONGTEXT NOT NULL,
              INDEX IDX_2CACE22F754B135B (public_landing_id),
              INDEX idx_public_landing_blocks_landing (public_landing_id, position),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE public_landing_blocks_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              public_landing_block_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:public_landing_block_id)',
              title VARCHAR(255) NOT NULL,
              text LONGTEXT NOT NULL,
              INDEX IDX_28893296D2764F41 (public_landing_block_id),
              PRIMARY KEY(locale, public_landing_block_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE public_landings (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:public_landing_id)',
              owner_type VARCHAR(10) DEFAULT NULL,
              owner_id VARCHAR(36) NOT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              active TINYINT(1) NOT NULL,
              title VARCHAR(255) NOT NULL,
              subtitle VARCHAR(255) NOT NULL,
              intro LONGTEXT NOT NULL,
              hero_image VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)',
              cta_label VARCHAR(255) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              UNIQUE INDEX uniq_public_landings_owner (owner_type, owner_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE public_landings_translation (
              locale VARCHAR(5) NOT NULL COMMENT '(DC2Type:locale)',
              public_landing_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:public_landing_id)',
              title VARCHAR(255) NOT NULL,
              subtitle VARCHAR(255) NOT NULL,
              intro LONGTEXT NOT NULL,
              cta_label VARCHAR(255) NOT NULL,
              INDEX IDX_10A73A99754B135B (public_landing_id),
              PRIMARY KEY(locale, public_landing_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              public_landing_blocks
            ADD
              CONSTRAINT FK_2CACE22F754B135B FOREIGN KEY (public_landing_id) REFERENCES public_landings (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              public_landing_blocks_translation
            ADD
              CONSTRAINT FK_28893296D2764F41 FOREIGN KEY (public_landing_block_id) REFERENCES public_landing_blocks (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              public_landings_translation
            ADD
              CONSTRAINT FK_10A73A99754B135B FOREIGN KEY (public_landing_id) REFERENCES public_landings (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE public_landing_blocks DROP FOREIGN KEY FK_2CACE22F754B135B');
        $this->addSql('ALTER TABLE public_landing_blocks_translation DROP FOREIGN KEY FK_28893296D2764F41');
        $this->addSql('ALTER TABLE public_landings_translation DROP FOREIGN KEY FK_10A73A99754B135B');
        $this->addSql('DROP TABLE public_landing_blocks');
        $this->addSql('DROP TABLE public_landing_blocks_translation');
        $this->addSql('DROP TABLE public_landings');
        $this->addSql('DROP TABLE public_landings_translation');
    }
}
