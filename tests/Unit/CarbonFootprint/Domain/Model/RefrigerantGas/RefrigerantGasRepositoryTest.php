<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RefrigerantGasRepositoryTest extends KernelTestCase
{
    public function testAddRefrigerantGasToRepository(): void
    {
        $repository = $this->createSUT();

        $refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();

        $this->assertNull($repository->find($refrigerantGas->id()));

        $repository->add($refrigerantGas);

        $recoveredRefrigerantGas = $repository->find($refrigerantGas->id());

        $this->assertNotNull($recoveredRefrigerantGas);
        $this->assertEquals($refrigerantGas->id(), $recoveredRefrigerantGas->id());
    }

    public function testRemoveRefrigerantGasToRepository(): void
    {
        $repository = $this->createSUT();

        $refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();

        $repository->add($refrigerantGas);

        $recoveredRefrigerantGas = $repository->find($refrigerantGas->id());

        $this->assertNotNull($recoveredRefrigerantGas);
        $this->assertEquals($refrigerantGas->id(), $recoveredRefrigerantGas->id());

        $repository->remove($refrigerantGas);

        $this->assertNull($repository->find($refrigerantGas->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::fromName('Nombre específico'));

        $filter = new RefrigerantGasFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new RefrigerantGasFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::random());
        $repository->add(RefrigerantGasMother::fromName('Nombre específico'));

        $filter = new RefrigerantGasFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();

        $repository->add($refrigerantGas);

        $recoveredRefrigerantGas = $repository->find($refrigerantGas->id());

        $this->assertNotNull($recoveredRefrigerantGas);

        $recoveredRefrigerantGas->remove();

        $repository->add($refrigerantGas);

        $this->assertNull($repository->find($refrigerantGas->id()));
    }

    abstract protected function createSUT(): RefrigerantGasRepository;
}
