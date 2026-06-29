<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\Model\Provider\ProviderFilter;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProviderRepositoryTest extends KernelTestCase
{
    public function testAddProviderToRepository(): void
    {
        $repository = $this->createSUT();

        $provider = ProviderMother::registeredProvider();

        $this->assertNull($repository->find($provider->id()));

        $repository->add($provider);

        $recoveredProvider = $repository->find($provider->id());

        $this->assertNotNull($recoveredProvider);
        $this->assertEquals($provider->id(), $recoveredProvider->id());
    }

    public function testRemoveProviderFromRepository(): void
    {
        $repository = $this->createSUT();

        $provider = ProviderMother::registeredProvider();

        $repository->add($provider);

        $recoveredProvider = $repository->find($provider->id());

        $this->assertNotNull($recoveredProvider);
        $this->assertEquals($provider->id(), $recoveredProvider->id());

        $repository->remove($provider);

        $this->assertNull($repository->find($provider->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::fromName('Proveedor específico'));

        $filter = new ProviderFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ProviderFilter(
            name: 'Proveedor específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::random());
        $repository->add(ProviderMother::fromName('Proveedor específico'));

        $filter = new ProviderFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $provider = ProviderMother::registeredProvider();

        $repository->add($provider);

        $recoveredProvider = $repository->find($provider->id());

        $this->assertNotNull($recoveredProvider);

        $recoveredProvider->remove();

        $repository->add($provider);

        $this->assertNull($repository->find($provider->id()));
    }

    abstract protected function createSUT(): ProviderRepository;
}
