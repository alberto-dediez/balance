<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\UI\Web\Navigation\AccountNodeProvider;
use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeSecurity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class NodeListerTest extends TestCase
{
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
        $this->assertTrue($adminNode->items[4]->selected);
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
            ['Cuentas', 'Usuarios', 'Firma de documentos', 'Registro'],
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
        );
    }
}
