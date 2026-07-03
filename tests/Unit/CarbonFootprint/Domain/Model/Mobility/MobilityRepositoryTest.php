<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MobilityRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMobilityToRepository(): void
    {
        $repository = $this->createSUT();

        $mobility = MobilityMother::registeredMobility();

        $this->assertNull($repository->find($mobility->id()));

        $repository->add($mobility);

        $recoveredMobility = $repository->find($mobility->id());

        $this->assertNotNull($recoveredMobility);
        $this->assertEquals($mobility->id(), $recoveredMobility->id());
    }

    public function testRemoveMobilityToRepository(): void
    {
        $repository = $this->createSUT();

        $mobility = MobilityMother::registeredMobility();

        $repository->add($mobility);

        $recoveredMobility = $repository->find($mobility->id());

        $this->assertNotNull($recoveredMobility);
        $this->assertEquals($mobility->id(), $recoveredMobility->id());

        $repository->remove($mobility);

        $this->assertNull($repository->find($mobility->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::fromName('Nombre específico'));

        $filter = new MobilityFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MobilityFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::fromName('Nombre específico'));

        $filter = new MobilityFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $mobility = MobilityMother::registeredMobility();

        $repository->add($mobility);

        $recoveredMobility = $repository->find($mobility->id());

        $this->assertNotNull($recoveredMobility);

        $recoveredMobility->remove();

        $repository->add($mobility);

        $this->assertNull($repository->find($mobility->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $mobility = MobilityMother::registeredMobility();

        $this->assertNull($repository->find($mobility->id()));

        $mobility->translate('translated-name', Locale::English);

        $repository->add($mobility);

        $recoveredMobility = $repository->find($mobility->id());

        $this->assertNotNull($recoveredMobility);
        $this->assertEquals($mobility->id(), $recoveredMobility->id());
        $this->assertEquals('translated-name', $recoveredMobility->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $mobility = MobilityMother::registeredMobility();

        $mobility->translate('translated-name', Locale::English);

        $repository->add($mobility);

        $recoveredMobilities = $repository->findByFilter(new MobilityFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredMobilities);
        $this->assertEquals($mobility->id(), $recoveredMobilities[0]->id());
        $this->assertEquals('translated-name', $recoveredMobilities[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MobilityRepository;
}
