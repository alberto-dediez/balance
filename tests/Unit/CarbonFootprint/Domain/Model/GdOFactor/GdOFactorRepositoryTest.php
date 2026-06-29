<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class GdOFactorRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddGdOFactorToRepository(): void
    {
        $repository = $this->createSUT();

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $this->assertNull($repository->find($gdOFactor->id()));

        $repository->add($gdOFactor);

        $recoveredGdOFactor = $repository->find($gdOFactor->id());

        $this->assertNotNull($recoveredGdOFactor);
        $this->assertEquals($gdOFactor->id(), $recoveredGdOFactor->id());
    }

    public function testRemoveGdOFactorToRepository(): void
    {
        $repository = $this->createSUT();

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $repository->add($gdOFactor);

        $recoveredGdOFactor = $repository->find($gdOFactor->id());

        $this->assertNotNull($recoveredGdOFactor);
        $this->assertEquals($gdOFactor->id(), $recoveredGdOFactor->id());

        $repository->remove($gdOFactor);

        $this->assertNull($repository->find($gdOFactor->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::fromName('Nombre específico'));

        $filter = new GdOFactorFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new GdOFactorFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::fromName('Nombre específico'));

        $filter = new GdOFactorFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $repository->add($gdOFactor);

        $recoveredGdOFactor = $repository->find($gdOFactor->id());

        $this->assertNotNull($recoveredGdOFactor);

        $recoveredGdOFactor->remove();

        $repository->add($gdOFactor);

        $this->assertNull($repository->find($gdOFactor->id()));
    }

    public function testFindOneByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(GdOFactorMother::random());
        $repository->add(GdOFactorMother::fromName('Nombre específico'));

        $found = $repository->findOneByName('Nombre específico');

        $this->assertNotNull($found);
        $this->assertEquals('Nombre específico', $found->name());

        $this->assertNull($repository->findOneByName('No existe'));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $this->assertNull($repository->find($gdOFactor->id()));

        $gdOFactor->translate('translated-name', Locale::English);

        $repository->add($gdOFactor);

        $recoveredGdOFactor = $repository->find($gdOFactor->id());

        $this->assertNotNull($recoveredGdOFactor);
        $this->assertEquals($gdOFactor->id(), $recoveredGdOFactor->id());
        $this->assertEquals('translated-name', $recoveredGdOFactor->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $gdOFactor->translate('translated-name', Locale::English);

        $repository->add($gdOFactor);

        $recoveredGdOFactors = $repository->findByFilter(new GdOFactorFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredGdOFactors);
        $this->assertEquals($gdOFactor->id(), $recoveredGdOFactors[0]->id());
        $this->assertEquals('translated-name', $recoveredGdOFactors[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): GdOFactorRepository;
}
