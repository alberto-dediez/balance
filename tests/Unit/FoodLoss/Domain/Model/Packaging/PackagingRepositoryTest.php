<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\Model\Packaging\PackagingFilter;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PackagingRepositoryTest extends KernelTestCase
{
    public function testAddPackagingToRepository(): void
    {
        $repository = $this->createSUT();

        $packaging = PackagingMother::registeredPackaging();

        $this->assertNull($repository->find($packaging->id()));

        $repository->add($packaging);

        $recoverPackaging = $repository->find($packaging->id());

        $this->assertNotNull($recoverPackaging);
        $this->assertEquals($packaging->id(), $recoverPackaging->id());
    }

    public function testRemovePackagingToRepository(): void
    {
        $repository = $this->createSUT();

        $packaging = PackagingMother::registeredPackaging();

        $repository->add($packaging);

        $recoverPackaging = $repository->find($packaging->id());

        $this->assertNotNull($recoverPackaging);
        $this->assertEquals($packaging->id(), $recoverPackaging->id());

        $repository->remove($packaging);

        $this->assertNull($repository->find($packaging->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::fromName('Nombre específico'));

        $filter = new PackagingFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PackagingFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::random());
        $repository->add(PackagingMother::fromName('Nombre específico'));

        $filter = new PackagingFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PackagingFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $packaging = PackagingMother::registeredPackaging();

        $repository->add($packaging);

        $recoverPackaging = $repository->find($packaging->id());

        $this->assertNotNull($recoverPackaging);

        $recoverPackaging->remove();

        $repository->add($packaging);

        $this->assertNull($repository->find($packaging->id()));
    }

    abstract protected function createSUT(): PackagingRepository;
}
