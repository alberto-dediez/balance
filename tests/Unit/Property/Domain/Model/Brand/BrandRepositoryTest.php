<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Brand;

use App\Property\Domain\Model\Brand\BrandFilter;
use App\Property\Domain\Model\Brand\BrandRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BrandRepositoryTest extends KernelTestCase
{
    public function testAddBrandToRepository(): void
    {
        $repository = $this->createSUT();

        $brand = BrandMother::random();

        $this->assertNull($repository->find($brand->id()));

        $repository->add($brand);

        $recoverBrand = $repository->find($brand->id());

        $this->assertNotNull($recoverBrand);
        $this->assertEquals($brand->id(), $recoverBrand->id());
    }

    public function testRemoveBrandToRepository(): void
    {
        $repository = $this->createSUT();

        $brand = BrandMother::random();

        $repository->add($brand);

        $recoverBrand = $repository->find($brand->id());

        $this->assertNotNull($recoverBrand);
        $this->assertEquals($brand->id(), $recoverBrand->id());

        $repository->remove($brand);

        $this->assertNull($repository->find($brand->id()));
    }

    public function testFindByName(): void
    {
        $repository = $this->createSUT();

        $brand = BrandMother::random();

        $this->assertNull($repository->findByName($brand->name()));

        $repository->add($brand);

        $recoverBrand = $repository->findByName($brand->name());

        $this->assertNotNull($recoverBrand);
        $this->assertEquals($brand->id(), $recoverBrand->id());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::fromName('Ibis'));

        $filter = new BrandFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new BrandFilter(
            name: 'Ibis',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::fromName('Ibis'));

        $filter = new BrandFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new BrandFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $brand = BrandMother::random();

        $repository->add($brand);

        $recoverBrand = $repository->find($brand->id());

        $this->assertNotNull($recoverBrand);

        $recoverBrand->remove();

        $repository->add($brand);

        $this->assertNull($repository->find($brand->id()));
    }

    abstract protected function createSUT(): BrandRepository;
}
