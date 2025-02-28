<?php

namespace App\Unit\Shared\Infrastructure\UI\Web\Navigation;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeSecurity;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NodeListerTest extends TestCase
{
    public function testNodesReturnsAtLeastUsersWhenLoggedAsAdmin(): void
    {
        $security = FakeSecurity::fromRole(Role::Admin);

        $sut = $this->createSUT($security);

        $result = $sut->nodes();

        $this->assertNotNull($result);
        $this->assertTrue(is_array($result));
        $this->assertGreaterThanOrEqual(1, count($result));

        $this->assertTrue($result[0] instanceof Node);
        $this->assertTrue($result[0]->items[0] instanceof Node);
    }

    private function createSUT(AuthorizationCheckerInterface $security): NodeLister
    {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')->willReturn('/es/admin/users');

        return new NodeLister($security, $router);
    }
}