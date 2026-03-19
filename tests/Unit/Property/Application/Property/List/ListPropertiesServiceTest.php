<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\List;

use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesResponse;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPropertiesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
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

    private function createSUT(InMemoryPropertyRepository $repository): ListPropertiesService
    {
        return new ListPropertiesService($repository, new PropertyAuthorizer());
    }
}
