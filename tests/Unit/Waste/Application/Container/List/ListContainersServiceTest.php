<?php

namespace App\Tests\Unit\Waste\Application\Container\List;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use PHPUnit\Framework\TestCase;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Waste\Application\Container\List\ListContainersRequest;
use App\Waste\Application\Container\List\ListContainersService;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\List\ListContainersResponse;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;

class ListContainersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $account = AccountMother::registeredAccount();
        $user = UserMother::fromUsernameAndAccount('test', $account);

        $repository->add(ContainerMother::fromAccount($account->id()));
        $repository->add(ContainerMother::fromAccount($account->id()));
        $repository->add(ContainerMother::fromAccount($account->id()));
        $repository->add(ContainerMother::fromAccount($account->id()));

        $request = new ListContainersRequest(
            accountId: $account->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListContainersResponse);
        $this->assertCount(4, $result->containers);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryContainerRepository $repository): ListContainersService
    {
        return new ListContainersService($repository, new ContainerAuthorizer());
    }
}