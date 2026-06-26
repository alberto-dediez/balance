<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CateringItemRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddCateringItemToRepository(): void
    {
        $repository = $this->createSUT();

        $cateringItem = CateringItemMother::registeredCateringItem();

        $this->assertNull($repository->find($cateringItem->id()));

        $repository->add($cateringItem);

        $recoveredCateringItem = $repository->find($cateringItem->id());

        $this->assertNotNull($recoveredCateringItem);
        $this->assertEquals($cateringItem->id(), $recoveredCateringItem->id());
    }

    public function testRemoveCateringItemToRepository(): void
    {
        $repository = $this->createSUT();

        $cateringItem = CateringItemMother::registeredCateringItem();

        $repository->add($cateringItem);

        $recoveredCateringItem = $repository->find($cateringItem->id());

        $this->assertNotNull($recoveredCateringItem);
        $this->assertEquals($cateringItem->id(), $recoveredCateringItem->id());

        $repository->remove($cateringItem);

        $this->assertNull($repository->find($cateringItem->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::fromName('Nombre específico'));

        $filter = new CateringItemFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CateringItemFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::fromName('Nombre específico'));

        $filter = new CateringItemFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $cateringItem = CateringItemMother::registeredCateringItem();

        $repository->add($cateringItem);

        $recoveredCateringItem = $repository->find($cateringItem->id());

        $this->assertNotNull($recoveredCateringItem);

        $recoveredCateringItem->remove();

        $repository->add($cateringItem);

        $this->assertNull($repository->find($cateringItem->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $cateringItem = CateringItemMother::registeredCateringItem();

        $this->assertNull($repository->find($cateringItem->id()));

        $cateringItem->translate('translated-name', Locale::English);

        $repository->add($cateringItem);

        $recoveredCateringItem = $repository->find($cateringItem->id());

        $this->assertNotNull($recoveredCateringItem);
        $this->assertEquals($cateringItem->id(), $recoveredCateringItem->id());
        $this->assertEquals('translated-name', $recoveredCateringItem->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $cateringItem = CateringItemMother::registeredCateringItem();

        $cateringItem->translate('translated-name', Locale::English);

        $repository->add($cateringItem);

        $recoveredCateringItems = $repository->findByFilter(new CateringItemFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredCateringItems);
        $this->assertEquals($cateringItem->id(), $recoveredCateringItems[0]->id());
        $this->assertEquals('translated-name', $recoveredCateringItems[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): CateringItemRepository;
}
