<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\List;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Application\GdOFactor\List\ListGdOFactorsRequest;
use App\CarbonFootprint\Application\GdOFactor\List\ListGdOFactorsService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListGdOFactorsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());

        $request = new ListGdOFactorsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->gdOFactors);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(GdOFactorMother::fromName('name-1'));
        $repository->add(GdOFactorMother::fromName('name-2'));
        $repository->add(GdOFactorMother::fromName('name-2'));
        $repository->add(GdOFactorMother::fromName('name-2'));

        $request = new ListGdOFactorsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->gdOFactors);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListGdOFactorsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->gdOFactors);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(GdOFactorRepository $repository): ListGdOFactorsService
    {
        return new ListGdOFactorsService($repository, new GdOFactorAuthorizer());
    }
}
