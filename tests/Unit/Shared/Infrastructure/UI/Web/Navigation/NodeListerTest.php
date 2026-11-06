<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\UI\Web\Navigation\AccountNodeProvider;
use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Application\Account\DistributorPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeSecurity;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class NodeListerTest extends TestCase
{
    private const string ACCOUNT_ID = 'account-id';

    private const array FEATURE_ROUTES = [
        'properties' => '/es/properties',
        'property_carbon_footprints' => '/es/property-carbon-footprints',
        'property_carbon_footprint_kpis' => '/es/property-carbon-footprints/kpis',
        'companies' => '/es/companies',
        'company_carbon_footprints' => '/es/company-carbon-footprints',
        'company_carbon_footprint_kpis' => '/es/company-carbon-footprints/kpis',
        'providers' => '/es/providers',
        'events' => '/es/events',
        'carbon_footprint_trip_calculator' => '/es/carbon-footprint/trip-calculator',
        'carbon_footprint_trip_calculator_responses' => '/es/carbon-footprint/trip-calculator/responses',
        'projects' => '/es/projects',
        'credits' => '/es/credits',
        'project_contributions' => '/es/credits/contributions',
        'my_balance' => '/es/my-balance',
        'my_payee' => '/es/my-payee',
        'admin_events' => '/es/admin/events',
        'distributors' => '/es/distributors',
    ];

    public function testNodesReturnsAtLeastUsersWhenLoggedAsAdmin(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security);

        $result = $sut->nodes();

        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testNodesMarksLinkAndParentAsSelectedWhenCurrentPathMatchesTarget(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/admin/users/edit/some-user-id');

        $adminNode = $this->findNodeByLabel($sut->nodes(), 'Administración');

        $this->assertNotNull($adminNode);
        $this->assertTrue($adminNode->selected);
        $this->assertTrue($adminNode->items[1]->selected);
        $this->assertTrue($adminNode->items[1]->items[2]->selected);
    }

    public function testNodesDoesNotMarkAnyNodeAsSelectedWhenCurrentPathDoesNotMatch(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/profile');

        $adminNode = $this->findNodeByLabel($sut->nodes(), 'Administración');

        $this->assertNotNull($adminNode);
        $this->assertFalse($adminNode->selected);
        $this->assertFalse($adminNode->items[1]->selected);
        $this->assertFalse($adminNode->items[1]->items[0]->selected);
    }

    public function testNodesMarksOnlyTheMostSpecificLinkWhenTargetsOverlap(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/admin/users/export', [
            'admin_users' => '/es/admin/users',
            'admin_events' => '/es/admin/users/export',
        ]);

        $result = $sut->nodes();

        $adminNode = $this->findNodeByLabel($result, 'Administración');

        $this->assertNotNull($adminNode);

        $organization = $adminNode->items[1];
        $audit = $adminNode->items[3];

        $this->assertFalse($organization->items[2]->selected);
        $this->assertFalse($organization->selected);
        $this->assertTrue($audit->items[1]->selected);
        $this->assertTrue($audit->selected);
        $this->assertTrue($adminNode->selected);
    }

    /**
     * @param list<Node> $nodes
     */
    private function findNodeByLabel(array $nodes, string $label): ?Node
    {
        foreach ($nodes as $node) {
            if ($node->label instanceof TranslatableMessage && $label === $node->label->getMessage()) {
                return $node;
            }
        }

        return null;
    }

    public function testAdminsSeeAdministrationGroupedInOrganizationAndAudit(): void
    {
        $adminNode = $this->findNodeByLabel($this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes(), 'Administración');

        $this->assertNotNull($adminNode);

        $items = $adminNode->items;

        $this->assertSame(['Tipos de cambio', 'Organización', 'Bioscore', 'Auditoría'], $this->labels($items));
        $this->assertSame([], $items[0]->items);
        $this->assertNull($items[1]->target);
        $this->assertNull($items[3]->target);
        $this->assertSame(['Cuentas', 'Empleados', 'Usuarios'], $this->labels($items[1]->items));
        $this->assertSame(['Combustibles de instalaciones', 'Combustibles de vehículos', 'Gases refrigerantes', 'Comercializadoras eléctricas'], $this->labels($items[2]->items));
        $this->assertSame(['Firma de documentos', 'Registro'], $this->labels($items[3]->items));

        $superAdmin = $this->createStub(AuthorizationCheckerInterface::class);
        $superAdmin->method('isGranted')->willReturnCallback(
            static fn(mixed $attribute): bool => in_array($attribute, [Role::Admin->value, Role::SuperAdmin->value], true),
        );

        $superAdminNode = $this->findNodeByLabel($this->createSUT($superAdmin)->nodes(), 'Administración');

        $this->assertNotNull($superAdminNode);
        $this->assertSame(['Firma de documentos', 'Emails enviados', 'Registro'], $this->labels($superAdminNode->items[3]->items));
    }

    public function testAdminsSeeMastersGroupedByActivity(): void
    {
        $masters = $this->findNodeByLabel($this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes(), 'Maestros');

        $this->assertNotNull($masters);
        $this->assertSame('solar:database-line-duotone', $masters->icon);

        $groups = $masters->items;

        $this->assertSame(['Materiales', 'Productos vendidos', 'Inversiones', 'Instalaciones', 'Transporte'], $this->labels($groups));

        foreach ($groups as $group) {
            $this->assertNull($group->target);
            $this->assertNull($group->icon);
        }

        $this->assertSame(['Materiales', 'Artículos de merchandising', 'Elementos de catering', 'Bienes y servicios comprados', 'Bienes de capital'], $this->labels($groups[0]->items));
        $this->assertSame(['Procesos', 'Perfiles de uso'], $this->labels($groups[1]->items));
        $this->assertSame(['Sectores económicos'], $this->labels($groups[2]->items));
        $this->assertSame(['Combustibles', 'Comercializadoras eléctricas', 'Factores GdO', 'Mix eléctrico', 'Gases refrigerantes', 'Residuos'], $this->labels($groups[3]->items));
        $this->assertSame(['Medios de transporte', 'Combustible de maquinaria', 'Movilidad', 'Pernoctaciones'], $this->labels($groups[4]->items));
    }

    public function testAbsorbedMastersAreNoLongerRoots(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes();

        foreach (['Materiales', 'Productos vendidos', 'Inversiones', 'Instalaciones', 'Transporte'] as $label) {
            $this->assertNull($this->findNodeByLabel($nodes, $label), $label);
        }

        foreach (['Medidas de mitigación', 'Bioscore', 'Materialidad'] as $label) {
            $this->assertNull($this->findNodeByLabel($nodes, $label), $label);
        }
    }

    public function testAdminsSeeCarbonFootprintLinksBeforeItsGroups(): void
    {
        $carbonFootprint = $this->findNodeByLabel($this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes(), 'Huella de carbono');

        $this->assertNotNull($carbonFootprint);

        $items = $carbonFootprint->items;

        $this->assertSame(['Informes de huella', 'Grupos de coeficientes', 'Medidas de mitigación', 'Materialidad'], $this->labels($items));
        $this->assertSame([], $items[0]->items);
        $this->assertSame([], $items[1]->items);
        $this->assertSame(['Medidas', 'Categorías de medidas'], $this->labels($items[2]->items));
        $this->assertSame(['Categorías', 'Preguntas', 'Sectores', 'Configuración'], $this->labels($items[3]->items));
    }

    public function testAdminRootsKeepTheirOrderAroundMasters(): void
    {
        $this->assertSame(
            ['Propiedades', 'Proyectos', 'Tesorería', 'Maestros', 'Huella de carbono', 'Administración', 'Biblioteca', 'Notificaciones', 'Novedades'],
            $this->labels($this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes()),
        );
    }

    public function testAMasterLeafSelectsItsGroupAndTheMastersRoot(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Admin), '/es/admin/combustibles', ['admin_combustibles' => '/es/admin/combustibles'])->nodes();

        $masters = $this->findNodeByLabel($nodes, 'Maestros');
        $administration = $this->findNodeByLabel($nodes, 'Administración');

        $this->assertNotNull($masters);
        $this->assertNotNull($administration);
        $this->assertTrue($masters->items[3]->items[0]->selected);
        $this->assertTrue($masters->items[3]->selected);
        $this->assertTrue($masters->selected);
        $this->assertFalse($masters->items[0]->selected);
        $this->assertFalse($administration->selected);
    }

    public function testEveryMasterTargetIsStillReachable(): void
    {
        $routes = [
            'admin_materials' => '/es/admin/materials',
            'admin_merchandising_items' => '/es/admin/merchandising-items',
            'admin_catering_items' => '/es/admin/catering-items',
            'admin_purchase_services' => '/es/admin/purchase-services',
            'admin_capital_goods' => '/es/admin/capital-goods',
            'admin_processes' => '/es/admin/processes',
            'admin_use_profiles' => '/es/admin/use-profiles',
            'admin_economic_sectors' => '/es/admin/economic-sectors',
            'admin_combustibles' => '/es/admin/combustibles',
            'admin_electricity_marketers' => '/es/admin/electricity-marketers',
            'admin_gdo_factors' => '/es/admin/gdo-factors',
            'admin_electricity_mixes' => '/es/admin/electricity-mixes',
            'admin_refrigerant_gases' => '/es/admin/refrigerant-gases',
            'admin_wastes' => '/es/admin/wastes',
            'admin_transports' => '/es/admin/transports',
            'admin_machinery_fuels' => '/es/admin/machinery-fuels',
            'admin_mobilities' => '/es/admin/mobilities',
            'admin_overnight_stays' => '/es/admin/overnight-stays',
        ];

        $targets = $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: $routes)->nodes());

        foreach ($routes as $name => $target) {
            $this->assertContains($target, $targets, $name);
        }
    }

    public function testOwnersAndUsersHaveNoGroups(): void
    {
        foreach ([Role::Owner, Role::User] as $role) {
            foreach ($this->createSUT(FakeSecurity::fromRole($role))->nodes() as $root) {
                foreach ($root->items as $item) {
                    $this->assertSame([], $item->items, $role->value);
                }
            }
        }
    }

    public function testUsersAndOwnersSeeAnnouncementsWhenEnabled(): void
    {
        foreach ([Role::User, Role::Owner] as $role) {
            $sut = $this->createSUT(FakeSecurity::fromRole($role), routes: ['announcements' => '/es/announcements']);

            $this->assertContains('/es/announcements', $this->targets($sut->nodes()), $role->value);
        }
    }

    public function testUsersAndOwnersDoNotSeeAnnouncementsWhenDisabled(): void
    {
        foreach ([Role::User, Role::Owner] as $role) {
            $sut = $this->createSUT(FakeSecurity::fromRole($role), routes: ['announcements' => '/es/announcements'], announcementsEnabled: false);

            $this->assertNotContains('/es/announcements', $this->targets($sut->nodes()), $role->value);
        }
    }

    public function testAdminsSeeAnnouncementsWhenDisabled(): void
    {
        $sut = $this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: ['announcements' => '/es/announcements'], announcementsEnabled: false);

        $this->assertContains('/es/announcements', $this->targets($sut->nodes()));
    }

    public function testAdminsAndOwnersSeePropertiesWhenEnabled(): void
    {
        $routes = ['admin_properties' => '/es/admin/properties', 'properties' => '/es/properties'];

        $this->assertContains('/es/admin/properties', $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: $routes)->nodes()));
        $this->assertContains('/es/properties', $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: $routes)->nodes()));
    }

    public function testAdminsAndOwnersDoNotSeePropertiesWhenDisabled(): void
    {
        $routes = ['admin_properties' => '/es/admin/properties', 'properties' => '/es/properties'];

        foreach ([Role::Admin, Role::Owner] as $role) {
            $nodes = $this->createSUT(FakeSecurity::fromRole($role), routes: $routes, propertiesEnabled: false)->nodes();

            $this->assertNotContains('/es/admin/properties', $this->targets($nodes), $role->value);
            $this->assertNotContains('/es/properties', $this->targets($nodes), $role->value);
            $this->assertNull($this->findNodeByLabel($nodes, 'Propiedades'), $role->value);
        }
    }

    public function testAdminsAndOwnersSeeEmployeesWhenEnabled(): void
    {
        $routes = ['admin_employees' => '/es/admin/employees', 'employees' => '/es/employees'];

        $this->assertContains('/es/admin/employees', $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: $routes)->nodes()));
        $this->assertContains('/es/employees', $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: $routes)->nodes()));
    }

    public function testAdminsAndOwnersDoNotSeeEmployeesWhenDisabled(): void
    {
        $routes = ['admin_employees' => '/es/admin/employees', 'employees' => '/es/employees'];

        foreach ([Role::Admin, Role::Owner] as $role) {
            $nodes = $this->createSUT(FakeSecurity::fromRole($role), routes: $routes, humanResourcesEnabled: false)->nodes();

            $this->assertNotNull($this->findNodeByLabel($nodes, 'Administración'), $role->value);
            $this->assertNotContains('/es/admin/employees', $this->targets($nodes), $role->value);
            $this->assertNotContains('/es/employees', $this->targets($nodes), $role->value);
        }
    }

    public function testAdministrationItemsKeepOrderWhenHumanResourcesDisabled(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Admin), humanResourcesEnabled: false)->nodes();

        $adminNode = $this->findNodeByLabel($nodes, 'Administración');

        $this->assertNotNull($adminNode);
        $this->assertSame(['Tipos de cambio', 'Organización', 'Bioscore', 'Auditoría'], $this->labels($adminNode->items));
        $this->assertSame(['Cuentas', 'Usuarios'], $this->labels($adminNode->items[1]->items));
        $this->assertSame(['Firma de documentos', 'Registro'], $this->labels($adminNode->items[3]->items));
    }

    public function testOwnersAndUsersReceiveProviderNodesFirst(): void
    {
        foreach ([Role::Owner, Role::User] as $role) {
            $sut = $this->createSUT(FakeSecurity::fromRole($role), accountNodeProvider: $this->accountNodeProvider([
                $this->group('Rating ESG', '/es/assessments/templates/t1'),
                $this->group('Anual', '/es/assessments/templates/t2'),
            ]));

            $result = $sut->nodes();

            $this->assertSame('Rating ESG', $result[0]->label, $role->value);
            $this->assertSame('/es/assessments/templates/t1', $result[0]->items[0]->target, $role->value);
            $this->assertSame('Anual', $result[1]->label, $role->value);
            $this->assertSame('/es/assessments/templates/t2', $result[1]->items[0]->target, $role->value);
        }
    }

    public function testOwnersAndUsersWithoutProviderNodesKeepTheDefaultMenu(): void
    {
        foreach ([Role::Owner, Role::User] as $role) {
            $expected = $this->targets($this->createSUT(FakeSecurity::fromRole($role))->nodes());

            foreach ([null, $this->accountNodeProvider([])] as $accountNodeProvider) {
                $sut = $this->createSUT(FakeSecurity::fromRole($role), accountNodeProvider: $accountNodeProvider);

                $this->assertSame($expected, $this->targets($sut->nodes()), $role->value);
            }
        }
    }

    public function testAdminsDoNotReceiveProviderNodes(): void
    {
        $sut = $this->createSUT(
            FakeSecurity::fromRole(Role::Admin),
            accountNodeProvider: $this->accountNodeProvider([$this->group('Rating ESG', '/es/assessments/templates/t1')]),
        );

        $result = $sut->nodes();

        $this->assertNotContains('/es/assessments/templates/t1', $this->targets($result));
        $this->assertSame($this->findNodeByLabel($result, 'Propiedades'), $result[0]);
    }

    public function testPreselectedItemRemainsSelectedWhenCurrentPathDoesNotMatch(): void
    {
        $sut = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            '/es/assessments/a1/answer',
            accountNodeProvider: $this->accountNodeProvider([$this->group('Rating ESG', '/es/assessments/templates/t1', true)]),
        );

        $result = $sut->nodes();

        $this->assertTrue($result[0]->items[0]->selected);
        $this->assertTrue($result[0]->selected);
    }

    public function testALeafOfAGroupMarksItselfItsGroupAndItsRootAsSelected(): void
    {
        $result = $this->createThreeLevelSUT('/es/b1')->nodes();

        $root = $result[0];
        [$loose, $groupA, $groupB] = $root->items;

        $this->assertTrue($groupB->items[0]->selected);
        $this->assertTrue($groupB->selected);
        $this->assertTrue($root->selected);
        $this->assertFalse($loose->selected);
        $this->assertFalse($groupA->selected);
        $this->assertFalse($groupA->items[0]->selected);
        $this->assertFalse($groupA->items[1]->selected);
        $this->assertFalse($result[1]->selected);
    }

    public function testTheMostSpecificLeafWinsAcrossLevels(): void
    {
        $result = $this->createThreeLevelSUT('/es/x/export')->nodes();

        [$loose, $groupA] = $result[0]->items;

        $this->assertTrue($groupA->items[0]->selected);
        $this->assertTrue($groupA->selected);
        $this->assertFalse($loose->selected);
    }

    public function testAGroupIsNeverSelectedByItsOwnTarget(): void
    {
        $result = $this->createThreeLevelSUT('/es/grupo-a')->nodes();

        $this->assertFalse($result[0]->items[1]->selected);
        $this->assertFalse($result[0]->selected);
    }

    public function testARootLeafIsStillSelected(): void
    {
        $result = $this->createThreeLevelSUT('/es/')->nodes();

        $this->assertTrue($result[1]->selected);
        $this->assertFalse($result[0]->selected);
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<string>
     */
    private function labels(array $nodes): array
    {
        return array_map(
            static fn(Node $node): string => $node->label instanceof TranslatableMessage ? $node->label->getMessage() : $node->label,
            $nodes,
        );
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<?string>
     */
    private function targets(array $nodes): array
    {
        $targets = [];

        foreach ($nodes as $node) {
            $targets = [...$targets, $node->target, ...$this->targets($node->items)];
        }

        return $targets;
    }

    private function group(string $label, string $target, bool $selected = false): Node
    {
        return new Node(
            label: $label,
            icon: 'solar:clipboard-list-line-duotone',
            items: [
                new Node(
                    label: 'Evaluaciones',
                    icon: 'solar:document-text-line-duotone',
                    target: $target,
                    selected: $selected,
                ),
            ],
        );
    }

    /**
     * @param list<Node> $nodes
     */
    private function accountNodeProvider(array $nodes): AccountNodeProvider
    {
        return new class ($nodes) implements AccountNodeProvider {
            /**
             * @param list<Node> $nodes
             */
            public function __construct(private readonly array $nodes) {}

            public function nodes(): array
            {
                return $this->nodes;
            }
        };
    }

    public function testOwnersWithAllFeaturesSeeEveryFeatureNode(): void
    {
        $targets = $this->ownerTargets(new AccountFeatures());

        foreach (self::FEATURE_ROUTES as $name => $target) {
            if ('admin_events' === $name) {
                continue;
            }

            $this->assertContains($target, $targets, $name);
        }
    }

    public function testOwnersWithoutEventsDoNotSeeEvents(): void
    {
        $targets = $this->ownerTargets(new AccountFeatures(events: false));

        $this->assertNotContains('/es/events', $targets);
        $this->assertContains('/es/providers', $targets);
        $this->assertContains('/es/properties', $targets);
    }

    public function testOwnersWithoutAccountTripCalculatorDoNotSeeTheTripCalculator(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: self::FEATURE_ROUTES, features: new AccountFeatures(accountTripCalculator: false))->nodes();

        $this->assertNull($this->findNodeByLabel($nodes, 'Calculadora de viajes'));
        $this->assertNotContains('/es/carbon-footprint/trip-calculator', $this->targets($nodes));
        $this->assertNotContains('/es/carbon-footprint/trip-calculator/responses', $this->targets($nodes));
    }

    public function testOwnersWithoutCompensationDoNotSeeProjects(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: self::FEATURE_ROUTES, features: new AccountFeatures(compensation: false))->nodes();
        $targets = $this->targets($nodes);

        $this->assertNull($this->findNodeByLabel($nodes, 'Proyectos'));

        foreach (['/es/projects', '/es/credits', '/es/credits/contributions', '/es/my-balance', '/es/my-payee'] as $target) {
            $this->assertNotContains($target, $targets, $target);
        }
    }

    public function testOwnersWithoutOrganizationalCarbonFootprintDoNotSeeFootprintsNorIndicators(): void
    {
        $targets = $this->ownerTargets(new AccountFeatures(organizationalCarbonFootprint: false));

        $this->assertContains('/es/properties', $targets);
        $this->assertContains('/es/companies', $targets);

        foreach (['/es/property-carbon-footprints', '/es/property-carbon-footprints/kpis', '/es/company-carbon-footprints', '/es/company-carbon-footprints/kpis'] as $target) {
            $this->assertNotContains($target, $targets, $target);
        }
    }

    public function testOwnersSeePropertiesAndCompaniesWithAnyOfTheirFeatures(): void
    {
        $cases = [
            'events' => new AccountFeatures(propertyTripCalculators: false, organizationalCarbonFootprint: false),
            'organizational carbon footprint' => new AccountFeatures(propertyTripCalculators: false, events: false),
            'property trip calculators' => new AccountFeatures(events: false, organizationalCarbonFootprint: false),
        ];

        foreach ($cases as $case => $features) {
            $targets = $this->ownerTargets($features);

            $this->assertContains('/es/properties', $targets, $case);
            $this->assertContains('/es/companies', $targets, $case);
        }
    }

    public function testOwnersDoNotSeePropertiesNorCompaniesWithoutAnyOfTheirFeatures(): void
    {
        $nodes = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            routes: self::FEATURE_ROUTES,
            features: new AccountFeatures(propertyTripCalculators: false, events: false, organizationalCarbonFootprint: false),
        )->nodes();

        $this->assertNull($this->findNodeByLabel($nodes, 'Propiedades'));
        $this->assertNull($this->findNodeByLabel($nodes, 'Empresas'));
    }

    public function testActiveDistributorWithoutFeaturesSeesPropertiesButNotCompanies(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', self::ACCOUNT_ID);
        $distributor->changeFeatures(new AccountFeatures(propertyTripCalculators: false, events: false, organizationalCarbonFootprint: false));

        $nodes = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            routes: self::FEATURE_ROUTES,
            features: new AccountFeatures(propertyTripCalculators: false, events: false, organizationalCarbonFootprint: false),
            distributorPolicy: DistributorPolicyMother::create([$distributor]),
        )->nodes();

        $this->assertNotNull($this->findNodeByLabel($nodes, 'Propiedades'));
        $this->assertContains('/es/properties', $this->targets($nodes));
        $this->assertNull($this->findNodeByLabel($nodes, 'Empresas'));
        $this->assertNotContains('/es/property-carbon-footprints', $this->targets($nodes));
    }

    public function testActiveDistributorWithoutCompensationSeesContributionsInProperties(): void
    {
        $features = new AccountFeatures(compensation: false);
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', self::ACCOUNT_ID);
        $distributor->changeFeatures($features);

        $nodes = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            routes: self::FEATURE_ROUTES,
            features: $features,
            distributorPolicy: DistributorPolicyMother::create([$distributor]),
        )->nodes();

        $properties = $this->findNodeByLabel($nodes, 'Propiedades');

        $this->assertNotNull($properties);
        $this->assertContains('/es/credits/contributions', array_map(static fn(Node $item): ?string => $item->target, $properties->items));
        $this->assertNull($this->findNodeByLabel($nodes, 'Proyectos'));
    }

    public function testActiveDistributorWithCompensationSeesContributionsOnlyInProjects(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', self::ACCOUNT_ID);

        $nodes = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            routes: self::FEATURE_ROUTES,
            features: new AccountFeatures(),
            distributorPolicy: DistributorPolicyMother::create([$distributor]),
        )->nodes();

        $properties = $this->findNodeByLabel($nodes, 'Propiedades');

        $this->assertNotNull($properties);
        $this->assertNotContains('/es/credits/contributions', array_map(static fn(Node $item): ?string => $item->target, $properties->items));
        $this->assertContains('/es/credits/contributions', $this->targets($nodes));
    }

    public function testOwnersSeeDistributorsInAdministrationAndAdminsInProperties(): void
    {
        $ownerNodes = $this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: self::FEATURE_ROUTES)->nodes();
        $administration = $this->findNodeByLabel($ownerNodes, 'Administración');

        $this->assertNotNull($administration);
        $this->assertContains('/es/distributors', array_map(static fn(Node $item): ?string => $item->target, $administration->items));

        $adminNodes = $this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: ['admin_distributor_assignments' => '/es/admin/distributor-assignments'])->nodes();
        $properties = $this->findNodeByLabel($adminNodes, 'Propiedades');

        $this->assertNotNull($properties);
        $this->assertContains('/es/admin/distributor-assignments', array_map(static fn(Node $item): ?string => $item->target, $properties->items));
    }

    public function testOwnersDoNotSeeDistributorsWhenPropertiesAreDisabled(): void
    {
        $nodes = $this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: self::FEATURE_ROUTES, propertiesEnabled: false)->nodes();

        $this->assertNotContains('/es/distributors', $this->targets($nodes));
    }

    public function testOwnersSeeProvidersWithEventsOrOrganizationalCarbonFootprint(): void
    {
        $this->assertContains('/es/providers', $this->ownerTargets(new AccountFeatures(organizationalCarbonFootprint: false)));
        $this->assertContains('/es/providers', $this->ownerTargets(new AccountFeatures(events: false)));
        $this->assertNotContains('/es/providers', $this->ownerTargets(new AccountFeatures(events: false, organizationalCarbonFootprint: false)));
    }

    public function testOwnersWithoutAnyFeatureOnlySeeTheCommonNodes(): void
    {
        $nodes = $this->createSUT(
            FakeSecurity::fromRole(Role::Owner),
            routes: self::FEATURE_ROUTES,
            features: new AccountFeatures(false, false, false, false, false),
        )->nodes();

        $labels = array_map(static fn(Node $node): string => $node->label instanceof TranslatableMessage ? $node->label->getMessage() : (string) $node->label, $nodes);

        $this->assertSame(['Administración', 'Biblioteca', 'Notificaciones', 'Novedades'], $labels);
    }

    public function testAdminsAreNotAffectedByTheFeaturesOfTheAccount(): void
    {
        $withFeatures = $this->createSUT(FakeSecurity::fromRole(Role::Admin), routes: self::FEATURE_ROUTES)->nodes();
        $withoutFeatures = $this->createSUT(
            FakeSecurity::fromRole(Role::Admin),
            routes: self::FEATURE_ROUTES,
            features: new AccountFeatures(false, false, false, false, false),
        )->nodes();

        $this->assertEquals($this->targets($withFeatures), $this->targets($withoutFeatures));
    }

    /**
     * @return list<?string>
     */
    private function ownerTargets(AccountFeatures $features): array
    {
        return $this->targets($this->createSUT(FakeSecurity::fromRole(Role::Owner), routes: self::FEATURE_ROUTES, features: $features)->nodes());
    }

    /**
     * @param array<string, string> $routes
     */
    private function createSUT(
        AuthorizationCheckerInterface $security,
        string $currentPath = '/es/admin/users',
        array $routes = [],
        bool $announcementsEnabled = true,
        ?AccountNodeProvider $accountNodeProvider = null,
        bool $propertiesEnabled = true,
        bool $humanResourcesEnabled = true,
        ?AccountFeatures $features = null,
        ?DistributorPolicy $distributorPolicy = null,
    ): NodeLister {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')->willReturnCallback(
            static fn(string $name): string => $routes[$name] ?? '/es/admin/users',
        );

        $requestStack = new RequestStack();
        $requestStack->push(Request::create($currentPath));

        return new NodeLister(
            $security,
            $router,
            $requestStack,
            announcementsEnabled: $announcementsEnabled,
            accountNodeProvider: $accountNodeProvider,
            propertiesEnabled: $propertiesEnabled,
            humanResourcesEnabled: $humanResourcesEnabled,
            accountFeaturesPolicy: $features instanceof AccountFeatures ? AccountFeaturesPolicyMother::forAccount(new AccountId(self::ACCOUNT_ID), $features) : null,
            currentAccountIdProvider: $features instanceof AccountFeatures ? new StubCurrentAccountIdProvider(new AccountId(self::ACCOUNT_ID)) : null,
            distributorPolicy: $distributorPolicy,
        );
    }

    private function createThreeLevelSUT(string $currentPath): NodeLister
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($currentPath));

        return new ThreeLevelNodeLister(FakeSecurity::fromRole(Role::Admin), $this->createMock(RouterInterface::class), $requestStack);
    }
}
