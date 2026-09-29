<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MitigationMeasureRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMitigationMeasureToRepository(): void
    {
        $repository = $this->createSUT();

        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $this->assertNull($repository->find($measure->id()));

        $repository->add($measure);

        $recoveredMeasure = $repository->find($measure->id());

        $this->assertNotNull($recoveredMeasure);
        $this->assertEquals($measure->id(), $recoveredMeasure->id());
    }

    public function testRemoveMitigationMeasureFromRepository(): void
    {
        $repository = $this->createSUT();

        $measure = MitigationMeasureMother::registeredMitigationMeasure();
        $repository->add($measure);

        $repository->remove($measure);

        $this->assertNull($repository->find($measure->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MitigationMeasureMother::random());
        $repository->add(MitigationMeasureMother::random());
        $repository->add(MitigationMeasureMother::fromName('Nombre específico'));

        $this->assertCount(3, $repository->findByFilter(new MitigationMeasureFilter()));
        $this->assertCount(1, $repository->findByFilter(new MitigationMeasureFilter(name: 'Nombre específico')));
        $this->assertSame(3, $repository->totalResultsByFilter(new MitigationMeasureFilter(limit: 1)));
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(MitigationMeasureMother::fromName('Bravo'));
        $repository->add(MitigationMeasureMother::fromName('Charlie'));
        $repository->add(MitigationMeasureMother::fromName('Alfa'));

        $this->assertSame(['Alfa', 'Bravo', 'Charlie'], $this->namesOf($repository->findByFilter(new MitigationMeasureFilter())));

        $filter = new MitigationMeasureFilter(limit: 2);
        $filter->orderWay = 'DESC';

        $this->assertSame(['Charlie', 'Bravo'], $this->namesOf($repository->findByFilter($filter)));

        $filter->page = 1;

        $this->assertSame(['Alfa'], $this->namesOf($repository->findByFilter($filter)));
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $measure = MitigationMeasureMother::registeredMitigationMeasure();
        $repository->add($measure);

        $measure->remove();
        $repository->add($measure);

        $this->assertNull($repository->find($measure->id()));
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $measure = MitigationMeasureMother::registeredMitigationMeasure();
        $measure->translate('translated-name', 'translated-description', Locale::English);
        $repository->add($measure);

        $measures = $repository->findByFilter(new MitigationMeasureFilter(name: 'translated-name'));

        $this->assertCount(1, $measures);
        $this->assertSame('translated-name', $measures[0]->name());
    }

    public function testFindByFilterByCategory(): void
    {
        $repository = $this->createSUT();
        $categoryId = MitigationMeasureCategoryId::generate();

        $repository->add(MitigationMeasureMother::from(name: 'first', id: (string) MitigationMeasureId::generate(), categoryId: $categoryId));
        $repository->add(MitigationMeasureMother::from(name: 'second', id: (string) MitigationMeasureId::generate(), categoryId: $categoryId));
        $repository->add(MitigationMeasureMother::random());

        $this->assertSame(2, $repository->totalResultsByFilter(new MitigationMeasureFilter(categoryId: $categoryId)));
    }

    public function testFindRecoverEffectsAndTranslations(): void
    {
        $repository = $this->createSUT(Locale::English);

        $measure = MitigationMeasureMother::withEffect(Source::FixedFacilities, 0.1, 0.3);
        $measure->translate('translated-name', 'translated-description', Locale::English);
        $repository->add($measure);

        $recoveredMeasure = $repository->find($measure->id());

        $this->assertNotNull($recoveredMeasure);
        $this->assertSame('translated-name', $recoveredMeasure->name());
        $this->assertSame('translated-description', $recoveredMeasure->description());
        $this->assertSame(0.3, $recoveredMeasure->effects()->forSource(Source::FixedFacilities)?->maximumReduction());
    }

    /**
     * @param array<int, MitigationMeasure> $measures
     *
     * @return array<int, string>
     */
    private function namesOf(array $measures): array
    {
        return array_map(static fn(MitigationMeasure $measure): string => $measure->name(), $measures);
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureRepository;
}
