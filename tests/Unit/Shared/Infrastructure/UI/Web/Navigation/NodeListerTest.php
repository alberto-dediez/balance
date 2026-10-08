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

        $items = $result[0]->items;

        $this->assertTrue($result[0]->selected);
        $this->assertFalse($items[1]->selected);
        $this->assertTrue($items[3]->selected);
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
}
