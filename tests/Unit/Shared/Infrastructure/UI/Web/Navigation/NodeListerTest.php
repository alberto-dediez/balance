<?php

declare(strict_types=1);

namespace App\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
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

    private function createSUT(AuthorizationCheckerInterface $security, string $currentPath = '/es/admin/users'): NodeLister
    {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')->willReturn('/es/admin/users');

        $requestStack = new RequestStack();
        $requestStack->push(Request::create($currentPath));

        return new NodeLister($security, $router, $requestStack);
    }
}
