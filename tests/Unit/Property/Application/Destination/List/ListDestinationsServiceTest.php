<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Destination\List;

use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Application\Destination\List\ListDestinationsRequest;
use App\Property\Application\Destination\List\ListDestinationsResponse;
use App\Property\Application\Destination\List\ListDestinationsService;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListDestinationsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());

        $request = new ListDestinationsRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListDestinationsResponse);
        $this->assertCount(4, $result->destinations);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryDestinationRepository $repository): ListDestinationsService
    {
        return new ListDestinationsService($repository, new DestinationAuthorizer());
    }
}
