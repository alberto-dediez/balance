<?php

namespace App\Tests\Unit\Property\Application\Property\List;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Property\Application\Property\List\ListPropertiesResponse;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class ListPropertiesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = PropertyRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());

        $request = new ListPropertiesRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListPropertiesResponse);
        $this->assertCount(4, $result->properties);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListPropertiesService($repository, new PropertyAuthorizer());
    }
}