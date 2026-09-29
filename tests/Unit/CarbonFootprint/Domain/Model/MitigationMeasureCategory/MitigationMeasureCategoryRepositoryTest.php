<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MitigationMeasureCategoryRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMitigationMeasureCategoryToRepository(): void
    {
        $repository = $this->createSUT();

        $category = MitigationMeasureCategoryMother::registeredMitigationMeasureCategory();

        $this->assertNull($repository->find($category->id()));

        $repository->add($category);

        $recoveredCategory = $repository->find($category->id());

        $this->assertNotNull($recoveredCategory);
        $this->assertEquals($category->id(), $recoveredCategory->id());
    }

    public function testRemoveMitigationMeasureCategoryFromRepository(): void
    {
        $repository = $this->createSUT();

        $category = MitigationMeasureCategoryMother::registeredMitigationMeasureCategory();
        $repository->add($category);

        $repository->remove($category);

        $this->assertNull($repository->find($category->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MitigationMeasureCategoryMother::random());
        $repository->add(MitigationMeasureCategoryMother::random());
        $repository->add(MitigationMeasureCategoryMother::fromName('Nombre específico'));

        $this->assertCount(3, $repository->findByFilter(new MitigationMeasureCategoryFilter()));
        $this->assertCount(1, $repository->findByFilter(new MitigationMeasureCategoryFilter(name: 'Nombre específico')));
        $this->assertSame(3, $repository->totalResultsByFilter(new MitigationMeasureCategoryFilter(limit: 1)));
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(MitigationMeasureCategoryMother::fromName('Bravo'));
        $repository->add(MitigationMeasureCategoryMother::fromName('Charlie'));
        $repository->add(MitigationMeasureCategoryMother::fromName('Alfa'));

        $this->assertSame(['Alfa', 'Bravo', 'Charlie'], $this->namesOf($repository->findByFilter(new MitigationMeasureCategoryFilter())));

        $filter = new MitigationMeasureCategoryFilter(limit: 2);
        $filter->orderWay = 'DESC';

        $this->assertSame(['Charlie', 'Bravo'], $this->namesOf($repository->findByFilter($filter)));

        $filter->page = 1;

        $this->assertSame(['Alfa'], $this->namesOf($repository->findByFilter($filter)));
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $category = MitigationMeasureCategoryMother::registeredMitigationMeasureCategory();
        $repository->add($category);

        $category->remove();
        $repository->add($category);

        $this->assertNull($repository->find($category->id()));
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $category = MitigationMeasureCategoryMother::registeredMitigationMeasureCategory();
        $category->translate('translated-name', Locale::English);
        $repository->add($category);

        $categories = $repository->findByFilter(new MitigationMeasureCategoryFilter(name: 'translated-name'));

        $this->assertCount(1, $categories);
        $this->assertSame('translated-name', $categories[0]->name());
    }

    /**
     * @param array<int, MitigationMeasureCategory> $categories
     *
     * @return array<int, string>
     */
    private function namesOf(array $categories): array
    {
        return array_map(static fn(MitigationMeasureCategory $category): string => $category->name(), $categories);
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureCategoryRepository;
}
