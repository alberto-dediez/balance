<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20261014081026 extends AbstractMigration
{
    private const array EUROPEAN_UNION_ISO = [
        'AUT', 'BEL', 'BGR', 'CYP', 'CZE', 'DEU', 'DNK', 'ESP', 'EST', 'FIN', 'FRA', 'GRC', 'HRV', 'HUN',
        'IRL', 'ITA', 'LTU', 'LUX', 'LVA', 'MLT', 'NLD', 'POL', 'PRT', 'ROU', 'SVK', 'SVN', 'SWE',
    ];

    public function getDescription(): string
    {
        return 'biohydra: Property, HumanResources and JobProgress tables, created only when missing';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $this->createCountries($schemaManager);
        $this->createDestinations($schemaManager);
        $this->createBrands($schemaManager);
        $this->createCategories($schemaManager);
        $this->createCompanies($schemaManager);
        $this->createProperties($schemaManager);
        $this->createEmployees($schemaManager);
        $this->createJobProgresses($schemaManager);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }

    private function createCountries(AbstractSchemaManager $schemaManager): void
    {
        if (!$schemaManager->tablesExist(['countries'])) {
            $this->addSql(<<<'SQL'
                CREATE TABLE countries (
                  id VARCHAR(36) NOT NULL COMMENT '(DC2Type:country_id)',
                  name VARCHAR(255) NOT NULL,
                  iso VARCHAR(10) DEFAULT NULL,
                  vat_rate DOUBLE PRECISION DEFAULT NULL,
                  european_union TINYINT(1) NOT NULL,
                  removed TINYINT(1) NOT NULL,
                  registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                  removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                  hotelbeds VARCHAR(36) DEFAULT NULL,
                  restel VARCHAR(36) DEFAULT NULL,
                  bioscore VARCHAR(36) DEFAULT NULL,
                  PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

            return;
        }

        $countries = $schemaManager->listTableColumns('countries');

        if (!array_key_exists('vat_rate', $countries)) {
            $this->addSql('ALTER TABLE countries ADD vat_rate DOUBLE PRECISION DEFAULT NULL');
        }

        if (array_key_exists('european_union', $countries)) {
            return;
        }

        $this->addSql('ALTER TABLE countries ADD european_union TINYINT(1) NOT NULL');

        $isoList = implode(', ', array_map(static fn(string $iso): string => sprintf("'%s'", $iso), self::EUROPEAN_UNION_ISO));

        $this->addSql(sprintf(<<<'SQL'
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
              WHERE UPPER(TRIM(iso)) IN (%s)
        SQL, $isoList));

        $this->addSql(sprintf(<<<'SQL'
            UPDATE countries
              SET european_union = 0, vat_rate = NULL
              WHERE iso IS NULL OR UPPER(TRIM(iso)) NOT IN (%s)
        SQL, $isoList));
    }

    private function createDestinations(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['destinations'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE destinations (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:destination_id)',
              name VARCHAR(255) NOT NULL,
              country_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:country_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              hotelbeds VARCHAR(36) DEFAULT NULL,
              restel VARCHAR(36) DEFAULT NULL,
              bioscore VARCHAR(36) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    private function createBrands(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['brands'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE brands (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:brand_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              hotelbeds VARCHAR(36) DEFAULT NULL,
              restel VARCHAR(36) DEFAULT NULL,
              bioscore VARCHAR(36) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    private function createCategories(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['categories'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE categories (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:category_id)',
              name VARCHAR(255) NOT NULL,
              removed TINYINT(1) DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              hotelbeds VARCHAR(36) DEFAULT NULL,
              restel VARCHAR(36) DEFAULT NULL,
              bioscore VARCHAR(36) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    private function createCompanies(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['companies'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE companies (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:company_id)',
              name VARCHAR(255) NOT NULL,
              vat_number VARCHAR(100) NOT NULL,
              account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
              removed TINYINT(1) DEFAULT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              street VARCHAR(512) DEFAULT NULL,
              postal_code VARCHAR(20) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              country VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    private function createProperties(AbstractSchemaManager $schemaManager): void
    {
        if (!$schemaManager->tablesExist(['properties'])) {
            $this->addSql(<<<'SQL'
                CREATE TABLE properties (
                  id VARCHAR(36) NOT NULL COMMENT '(DC2Type:property_id)',
                  name VARCHAR(255) NOT NULL,
                  account_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:account_id)',
                  company_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:company_id)',
                  number_of_employees INT DEFAULT NULL,
                  daily_clients INT DEFAULT NULL,
                  removed TINYINT(1) DEFAULT NULL,
                  registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                  removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                  property_type VARCHAR(255) DEFAULT NULL,
                  category_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:category_id)',
                  brand_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:brand_id)',
                  address VARCHAR(1024) DEFAULT NULL,
                  postal_code VARCHAR(20) DEFAULT NULL,
                  longitude NUMERIC(14, 10) DEFAULT NULL,
                  latitude NUMERIC(14, 10) DEFAULT NULL,
                  rooms INT DEFAULT NULL,
                  zone VARCHAR(512) DEFAULT NULL,
                  web VARCHAR(1024) DEFAULT NULL,
                  destination_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:destination_id)',
                  country_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:country_id)',
                  place_reference VARCHAR(512) DEFAULT NULL,
                  place_label VARCHAR(512) DEFAULT NULL,
                  hotelbeds VARCHAR(36) DEFAULT NULL,
                  restel VARCHAR(36) DEFAULT NULL,
                  bioscore VARCHAR(36) DEFAULT NULL,
                  PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

            return;
        }

        $properties = $schemaManager->listTableColumns('properties');

        $columns = [
            'company_id' => "VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:company_id)'",
            'number_of_employees' => 'INT DEFAULT NULL',
            'daily_clients' => 'INT DEFAULT NULL',
            'place_reference' => 'VARCHAR(512) DEFAULT NULL',
            'place_label' => 'VARCHAR(512) DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!array_key_exists($column, $properties)) {
                $this->addSql(sprintf('ALTER TABLE properties ADD %s %s', $column, $definition));
            }
        }
    }

    private function createEmployees(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['employees'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE employees (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:employee_id)',
              name VARCHAR(255) NOT NULL,
              email VARCHAR(255) NOT NULL,
              phone VARCHAR(20) DEFAULT NULL,
              account_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:account_id)',
              user_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)',
              removed TINYINT(1) NOT NULL,
              registered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              street VARCHAR(512) DEFAULT NULL,
              postal_code VARCHAR(20) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              country VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    private function createJobProgresses(AbstractSchemaManager $schemaManager): void
    {
        if ($schemaManager->tablesExist(['job_progresses'])) {
            return;
        }

        $this->addSql(<<<'SQL'
            CREATE TABLE job_progresses (
              id VARCHAR(36) NOT NULL COMMENT '(DC2Type:job_progress_id)',
              progress_key VARCHAR(500) NOT NULL COMMENT '(DC2Type:job_progress_key)',
              total INT NOT NULL,
              current_count INT NOT NULL,
              current_step VARCHAR(255) NOT NULL,
              status VARCHAR(255) NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }
}
