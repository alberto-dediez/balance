<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EmissionFactorGroupRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddEmissionFactorGroupToRepository(): void
    {
        $repository = $this->createSUT();

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $this->assertNull($repository->find($emissionFactorGroup->id()));

        $repository->add($emissionFactorGroup);

        $recoveredEmissionFactorGroup = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($recoveredEmissionFactorGroup);
        $this->assertEquals($emissionFactorGroup->id(), $recoveredEmissionFactorGroup->id());
    }

    public function testRemoveEmissionFactorGroupToRepository(): void
    {
        $repository = $this->createSUT();

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $repository->add($emissionFactorGroup);

        $recoveredEmissionFactorGroup = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($recoveredEmissionFactorGroup);
        $this->assertEquals($emissionFactorGroup->id(), $recoveredEmissionFactorGroup->id());

        $repository->remove($emissionFactorGroup);

        $this->assertNull($repository->find($emissionFactorGroup->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::fromName('Nombre específico'));

        $filter = new EmissionFactorGroupFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new EmissionFactorGroupFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::random());
        $repository->add(EmissionFactorGroupMother::fromName('Nombre específico'));

        $filter = new EmissionFactorGroupFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $repository->add($emissionFactorGroup);

        $recoveredEmissionFactorGroup = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($recoveredEmissionFactorGroup);

        $recoveredEmissionFactorGroup->remove();

        $repository->add($emissionFactorGroup);

        $this->assertNull($repository->find($emissionFactorGroup->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $this->assertNull($repository->find($emissionFactorGroup->id()));

        $emissionFactorGroup->translate('translated-name', Locale::English);

        $repository->add($emissionFactorGroup);

        $recoveredEmissionFactorGroup = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($recoveredEmissionFactorGroup);
        $this->assertEquals($emissionFactorGroup->id(), $recoveredEmissionFactorGroup->id());
        $this->assertEquals('translated-name', $recoveredEmissionFactorGroup->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $emissionFactorGroup->translate('translated-name', Locale::English);

        $repository->add($emissionFactorGroup);

        $recoveredEmissionFactorGroups = $repository->findByFilter(new EmissionFactorGroupFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredEmissionFactorGroups);
        $this->assertEquals($emissionFactorGroup->id(), $recoveredEmissionFactorGroups[0]->id());
        $this->assertEquals('translated-name', $recoveredEmissionFactorGroups[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): EmissionFactorGroupRepository;
}
