<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\UI\Web\Navigation\AccountNodeProvider;
use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
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

        $result = $sut->nodes();

        $this->assertTrue($result[0]->selected);
        $this->assertTrue($result[0]->items[0]->selected);
    }

    public function testNodesDoesNotMarkAnyNodeAsSelectedWhenCurrentPathDoesNotMatch(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/profile');

        $result = $sut->nodes();

        $this->assertFalse($result[0]->selected);
        $this->assertFalse($result[0]->items[0]->selected);
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
        $this->assertTrue($adminNode->selected);
        $this->assertFalse($adminNode->items[2]->selected);
        $this->assertTrue($adminNode->items[5]->selected);
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
        $this->assertSame(
            ['Cuentas', 'Usuarios', 'Firma de documentos', 'Informes de huella', 'Registro', 'Grupos de coeficientes', 'Tipos de cambio'],
            array_map(static fn(Node $item): string => $item->label instanceof TranslatableMessage ? $item->label->getMessage() : (string) $item->label, $adminNode->items),
        );
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

    /**
     * @param list<Node> $nodes
     *
     * @return list<?string>
     */
    private function targets(array $nodes): array
    {
        $targets = [];

        foreach ($nodes as $node) {
            $targets[] = $node->target;

            foreach ($node->items as $item) {
                $targets[] = $item->target;
            }
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
        );
    }
}
