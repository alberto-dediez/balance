<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260827150040 extends AbstractMigration
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
              countries
            ADD
              vat_rate DOUBLE PRECISION DEFAULT NULL,
            ADD
              european_union TINYINT(1) NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE countries
              SET european_union = 1,
                  vat_rate = CASE UPPER(TRIM(iso))
                      WHEN 'AUT' THEN 20.0 WHEN 'BEL' THEN 21.0 WHEN 'BGR' THEN 20.0
                      WHEN 'CYP' THEN 19.0 WHEN 'CZE' THEN 21.0 WHEN 'DEU' THEN 19.0
                      WHEN 'DNK' THEN 25.0 WHEN 'ESP' THEN 21.0 WHEN 'EST' THEN 22.0
                      WHEN 'FIN' THEN 25.5 WHEN 'FRA' THEN 20.0 WHEN 'GRC' THEN 24.0
                      WHEN 'HRV' THEN 25.0 WHEN 'HUN' THEN 27.0 WHEN 'IRL' THEN 23.0
                      WHEN 'ITA' THEN 22.0 WHEN 'LTU' THEN 21.0 WHEN 'LUX' THEN 17.0
                      WHEN 'LVA' THEN 21.0 WHEN 'MLT' THEN 18.0 WHEN 'NLD' THEN 21.0
                      WHEN 'POL' THEN 23.0 WHEN 'PRT' THEN 23.0 WHEN 'ROU' THEN 21.0
                      WHEN 'SVK' THEN 23.0 WHEN 'SVN' THEN 22.0 WHEN 'SWE' THEN 25.0
                  END
              WHERE UPPER(TRIM(iso)) IN (
                'AUT','BEL','BGR','CYP','CZE','DEU','DNK','ESP','EST','FIN','FRA','GRC','HRV','HUN',
                'IRL','ITA','LTU','LUX','LVA','MLT','NLD','POL','PRT','ROU','SVK','SVN','SWE'
            );
        SQL);

        $this->addSql(<<<'SQL'
          UPDATE countries
              SET european_union = 0, vat_rate = NULL
              WHERE iso IS NULL OR UPPER(TRIM(iso)) NOT IN (
                    'AUT','BEL','BGR','CYP','CZE','DEU','DNK','ESP','EST','FIN','FRA','GRC','HRV','HUN',
                    'IRL','ITA','LTU','LUX','LVA','MLT','NLD','POL','PRT','ROU','SVK','SVN','SWE'
            );
        SQL);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE countries DROP vat_rate, DROP european_union');
    }
}
