<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\WasteManager\List;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Waste\Application\WasteManager\List\ListWasteManagersRequest;
use App\Waste\Application\WasteManager\List\ListWasteManagersResponse;
use App\Waste\Application\WasteManager\List\ListWasteManagersService;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;
use PHPUnit\Framework\TestCase;

class ListWasteManagersServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($repository);

        $repository->add(WasteManagerMother::fromAccount($user->accountId()));
        $repository->add(WasteManagerMother::fromAccount($user->accountId()));
        $repository->add(WasteManagerMother::fromAccount($user->accountId()));
        $repository->add(WasteManagerMother::random());

        $request = new ListWasteManagersRequest(
            accountId: $user->accountId(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWasteManagersResponse);
        $this->assertCount(3, $result->wasteManagers);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(InMemoryWasteManagerRepository $repository): ListWasteManagersService
    {
        return new ListWasteManagersService($repository, new WasteManagerAuthorizer());
    }
}
