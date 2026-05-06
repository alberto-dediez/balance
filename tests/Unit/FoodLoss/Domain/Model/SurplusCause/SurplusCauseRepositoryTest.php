<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseFilter;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class SurplusCauseRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddSurplusCauseToRepository(): void
    {
        $repository = $this->createSUT();

        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $this->assertNull($repository->find($surplusCause->id()));

        $repository->add($surplusCause);

        $recoverSurplusCause = $repository->find($surplusCause->id());

        $this->assertNotNull($recoverSurplusCause);
        $this->assertEquals($surplusCause->id(), $recoverSurplusCause->id());
    }

    public function testRemoveSurplusCauseToRepository(): void
    {
        $repository = $this->createSUT();

        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $repository->add($surplusCause);

        $recoverSurplusCause = $repository->find($surplusCause->id());

        $this->assertNotNull($recoverSurplusCause);
        $this->assertEquals($surplusCause->id(), $recoverSurplusCause->id());

        $repository->remove($surplusCause);

        $this->assertNull($repository->find($surplusCause->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::fromName('Nombre específico'));

        $filter = new SurplusCauseFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new SurplusCauseFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::fromName('Nombre específico'));

        $filter = new SurplusCauseFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new SurplusCauseFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $repository->add($surplusCause);

        $recoverSurplusCause = $repository->find($surplusCause->id());

        $this->assertNotNull($recoverSurplusCause);

        $recoverSurplusCause->remove();

        $repository->add($surplusCause);

        $this->assertNull($repository->find($surplusCause->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $this->assertNull($repository->find($surplusCause->id()));

        $surplusCause->translate('translated-name', Locale::English);

        $repository->add($surplusCause);

        $recoverSurplusCause = $repository->find($surplusCause->id());

        $this->assertNotNull($recoverSurplusCause);
        $this->assertEquals($surplusCause->id(), $recoverSurplusCause->id());

        $this->assertEquals('translated-name', $recoverSurplusCause->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $this->assertNull($repository->find($surplusCause->id()));

        $surplusCause->translate('translated-name', Locale::English);

        $repository->add($surplusCause);

        $recoverSurplusCauses = $repository->findByFilter(new SurplusCauseFilter(name: 'translated-name'));

        $this->assertCount(1, $recoverSurplusCauses);
        $this->assertEquals($surplusCause->id(), $recoverSurplusCauses[0]->id());

        $this->assertEquals('translated-name', $recoverSurplusCauses[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): SurplusCauseRepository;
}
