<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
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
        $this->assertTrue($result[0]->items[0]->items[1]->selected);
    }

    public function testNodesDoesNotMarkAnyNodeAsSelectedWhenCurrentPathDoesNotMatch(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/profile');

        $result = $sut->nodes();

        $this->assertFalse($result[0]->selected);
        $this->assertFalse($result[0]->items[0]->selected);
        $this->assertFalse($result[0]->items[0]->items[0]->selected);
    }

    public function testNodesMarksOnlyTheMostSpecificLinkWhenTargetsOverlap(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security, '/es/admin/users/export', [
            'admin_users' => '/es/admin/users',
            'admin_events' => '/es/admin/users/export',
        ]);

        $result = $sut->nodes();

        $organization = $result[0]->items[0];
        $audit = $result[0]->items[1];

        $this->assertFalse($organization->items[1]->selected);
        $this->assertFalse($organization->selected);
        $this->assertTrue($audit->items[1]->selected);
        $this->assertTrue($audit->selected);
        $this->assertTrue($result[0]->selected);
    }

    public function testAdminsSeeSecurityGroupedInOrganizationAndAudit(): void
    {
        $groups = $this->createSUT(FakeSecurity::fromRole(Role::Admin))->nodes()[0]->items;

        $this->assertSame(['Organización', 'Auditoría'], $this->labels($groups));
        $this->assertNull($groups[0]->target);
        $this->assertNull($groups[1]->target);
        $this->assertSame(['Cuentas', 'Usuarios'], $this->labels($groups[0]->items));
        $this->assertSame(['Firma de documentos', 'Registro'], $this->labels($groups[1]->items));

        $superAdmin = $this->createStub(AuthorizationCheckerInterface::class);
        $superAdmin->method('isGranted')->willReturnCallback(
            static fn(mixed $attribute): bool => in_array($attribute, [Role::Admin->value, Role::SuperAdmin->value], true),
        );

        $superAdminGroups = $this->createSUT($superAdmin)->nodes()[0]->items;

        $this->assertSame(['Firma de documentos', 'Emails enviados', 'Registro'], $this->labels($superAdminGroups[1]->items));
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

    /**
     * @param array<string, string> $routes
     */
    private function createSUT(
        AuthorizationCheckerInterface $security,
        string $currentPath = '/es/admin/users',
        array $routes = [],
        bool $announcementsEnabled = true,
    ): NodeLister {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')->willReturnCallback(
            static fn(string $name): string => $routes[$name] ?? '/es/admin/users',
        );

        $requestStack = new RequestStack();
        $requestStack->push(Request::create($currentPath));

        return new NodeLister($security, $router, $requestStack, announcementsEnabled: $announcementsEnabled);
    }

    private function createThreeLevelSUT(string $currentPath): NodeLister
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($currentPath));

        return new ThreeLevelNodeLister(FakeSecurity::fromRole(Role::Admin), $this->createMock(RouterInterface::class), $requestStack);
    }
}
