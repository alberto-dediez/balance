<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup\List;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Application\EmissionFactorGroup\List\ListEmissionFactorGroupsRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\List\ListEmissionFactorGroupsService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmissionFactorGroupsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());

        $request = new ListEmissionFactorGroupsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->emissionFactorGroups);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(EmissionFactorGroupMother::fromName('name-1'));
        $repository->add(EmissionFactorGroupMother::fromName('name-2'));
        $repository->add(EmissionFactorGroupMother::fromName('name-2'));
        $repository->add(EmissionFactorGroupMother::fromName('name-2'));

        $request = new ListEmissionFactorGroupsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->emissionFactorGroups);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListEmissionFactorGroupsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->emissionFactorGroups);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(EmissionFactorGroupRepository $repository): ListEmissionFactorGroupsService
    {
        return new ListEmissionFactorGroupsService($repository, new EmissionFactorGroupAuthorizer());
    }
}
