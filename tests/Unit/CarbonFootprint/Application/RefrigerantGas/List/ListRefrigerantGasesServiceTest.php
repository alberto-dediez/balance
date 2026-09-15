<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\List;

use App\CarbonFootprint\Application\RefrigerantGas\List\ListRefrigerantGasesRequest;
use App\CarbonFootprint\Application\RefrigerantGas\List\ListRefrigerantGasesService;
use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListRefrigerantGasesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());

        $request = new ListRefrigerantGasesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->refrigerantGases);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(RefrigerantGasMother::fromName('name-1'));
        $repository->add(RefrigerantGasMother::fromName('name-2'));
        $repository->add(RefrigerantGasMother::fromName('name-2'));
        $repository->add(RefrigerantGasMother::fromName('name-2'));

        $request = new ListRefrigerantGasesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->refrigerantGases);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListRefrigerantGasesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->refrigerantGases);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(RefrigerantGasRepository $repository): ListRefrigerantGasesService
    {
        return new ListRefrigerantGasesService($repository, new RefrigerantGasAuthorizer());
    }
}
