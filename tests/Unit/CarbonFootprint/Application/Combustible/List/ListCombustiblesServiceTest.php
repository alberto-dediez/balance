<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\List;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\List\ListCombustiblesRequest;
use App\CarbonFootprint\Application\Combustible\List\ListCombustiblesService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCombustiblesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());

        $request = new ListCombustiblesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->combustibles);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CombustibleMother::fromName('name-1'));
        $repository->add(CombustibleMother::fromName('name-2'));
        $repository->add(CombustibleMother::fromName('name-2'));
        $repository->add(CombustibleMother::fromName('name-2'));

        $request = new ListCombustiblesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->combustibles);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListCombustiblesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->combustibles);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(CombustibleRepository $repository): ListCombustiblesService
    {
        return new ListCombustiblesService($repository, new CombustibleAuthorizer());
    }
}
