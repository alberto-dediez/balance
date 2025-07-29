<?php

namespace App\Tests\Unit\Property\Application\Destination\List;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Property\Application\Destination\List\ListDestinationsRequest;
use App\Property\Application\Destination\List\ListDestinationsService;
use App\Property\Application\Destination\List\ListDestinationsResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class ListDestinationsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
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

    private function createSUT($repository)
    {
        return new ListDestinationsService($repository, new DestinationAuthorizer());
    }
}