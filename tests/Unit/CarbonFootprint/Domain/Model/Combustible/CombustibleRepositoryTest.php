<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CombustibleRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddCombustibleToRepository(): void
    {
        $repository = $this->createSUT();

        $combustible = CombustibleMother::registeredCombustible();

        $this->assertNull($repository->find($combustible->id()));

        $repository->add($combustible);

        $recoveredCombustible = $repository->find($combustible->id());

        $this->assertNotNull($recoveredCombustible);
        $this->assertEquals($combustible->id(), $recoveredCombustible->id());
    }

    public function testRemoveCombustibleToRepository(): void
    {
        $repository = $this->createSUT();

        $combustible = CombustibleMother::registeredCombustible();

        $repository->add($combustible);

        $recoveredCombustible = $repository->find($combustible->id());

        $this->assertNotNull($recoveredCombustible);
        $this->assertEquals($combustible->id(), $recoveredCombustible->id());

        $repository->remove($combustible);

        $this->assertNull($repository->find($combustible->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::fromName('Nombre específico'));

        $filter = new CombustibleFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CombustibleFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::fromName('Nombre específico'));

        $filter = new CombustibleFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $combustible = CombustibleMother::registeredCombustible();

        $repository->add($combustible);

        $recoveredCombustible = $repository->find($combustible->id());

        $this->assertNotNull($recoveredCombustible);

        $recoveredCombustible->remove();

        $repository->add($combustible);

        $this->assertNull($repository->find($combustible->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $combustible = CombustibleMother::registeredCombustible();

        $this->assertNull($repository->find($combustible->id()));

        $combustible->translate('translated-name', Locale::English);

        $repository->add($combustible);

        $recoveredCombustible = $repository->find($combustible->id());

        $this->assertNotNull($recoveredCombustible);
        $this->assertEquals($combustible->id(), $recoveredCombustible->id());
        $this->assertEquals('translated-name', $recoveredCombustible->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $combustible = CombustibleMother::registeredCombustible();

        $combustible->translate('translated-name', Locale::English);

        $repository->add($combustible);

        $recoveredCombustibles = $repository->findByFilter(new CombustibleFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredCombustibles);
        $this->assertEquals($combustible->id(), $recoveredCombustibles[0]->id());
        $this->assertEquals('translated-name', $recoveredCombustibles[0]->name());
    }

    public function testFindOneByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(CombustibleMother::random());
        $repository->add(CombustibleMother::fromName('Nombre específico'));

        $found = $repository->findOneByName('Nombre específico');

        $this->assertNotNull($found);
        $this->assertEquals('Nombre específico', $found->name());

        $this->assertNull($repository->findOneByName('No existe'));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): CombustibleRepository;
}
