<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop all FoodLoss BC tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        // Link / child tables first
        $this->addSql('DROP TABLE IF EXISTS critical_point_action_link');
        $this->addSql('DROP TABLE IF EXISTS critical_point_reason_link');
        $this->addSql('DROP TABLE IF EXISTS critical_point_service_categories');
        $this->addSql('DROP TABLE IF EXISTS critical_point_surplus_causes');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_proposed_actions');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_section_translation');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_sections');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_version_properties');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_version_translations');
        $this->addSql('DROP TABLE IF EXISTS prevention_plan_versions');
        $this->addSql('DROP TABLE IF EXISTS prevention_plans');
        $this->addSql('DROP TABLE IF EXISTS action_translation');
        $this->addSql('DROP TABLE IF EXISTS action');
        $this->addSql('DROP TABLE IF EXISTS reason_translation');
        $this->addSql('DROP TABLE IF EXISTS reasons');
        $this->addSql('DROP TABLE IF EXISTS service_category_translation');
        $this->addSql('DROP TABLE IF EXISTS service_category');
        $this->addSql('DROP TABLE IF EXISTS surplus_cause_translations');
        $this->addSql('DROP TABLE IF EXISTS surplus_causes');
        $this->addSql('DROP TABLE IF EXISTS surpluses');
        $this->addSql('DROP TABLE IF EXISTS critical_points');
        $this->addSql('DROP TABLE IF EXISTS delivery_logs');
        $this->addSql('DROP TABLE IF EXISTS doggy_bag_exceptions');
        $this->addSql('DROP TABLE IF EXISTS doggy_bag_signs');
        $this->addSql('DROP TABLE IF EXISTS donation_agreements');
        $this->addSql('DROP TABLE IF EXISTS food_waste_managers');
        $this->addSql('DROP TABLE IF EXISTS incidence_evidences');
        $this->addSql('DROP TABLE IF EXISTS incidences');
        $this->addSql('DROP TABLE IF EXISTS packaging_property_stocks');
        $this->addSql('DROP TABLE IF EXISTS packagings');
        $this->addSql('DROP TABLE IF EXISTS protocol_accesses');
        $this->addSql('DROP TABLE IF EXISTS protocols');
        $this->addSql('DROP TABLE IF EXISTS recipient_organizations');
        $this->addSql('DROP TABLE IF EXISTS training_attendances');
        $this->addSql('DROP TABLE IF EXISTS training_sessions');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('FoodLoss tables cannot be restored automatically.');
    }
}
