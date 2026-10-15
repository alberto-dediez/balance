<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\List;

use App\CarbonFootprint\Application\UseProfile\List\ListUseProfilesRequest;
use App\CarbonFootprint\Application\UseProfile\List\ListUseProfilesService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListUseProfilesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());

        $request = new ListUseProfilesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->useProfiles);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(UseProfileMother::fromName('name-1'));
        $repository->add(UseProfileMother::fromName('name-2'));
        $repository->add(UseProfileMother::fromName('name-2'));
        $repository->add(UseProfileMother::fromName('name-2'));

        $request = new ListUseProfilesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->useProfiles);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListUseProfilesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->useProfiles);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(UseProfileRepository $repository): ListUseProfilesService
    {
        return new ListUseProfilesService($repository, new UseProfileAuthorizer());
    }
}
