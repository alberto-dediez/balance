<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MerchandisingItemRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMerchandisingItemToRepository(): void
    {
        $repository = $this->createSUT();

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $this->assertNull($repository->find($merchandisingItem->id()));

        $repository->add($merchandisingItem);

        $recoveredMerchandisingItem = $repository->find($merchandisingItem->id());

        $this->assertNotNull($recoveredMerchandisingItem);
        $this->assertEquals($merchandisingItem->id(), $recoveredMerchandisingItem->id());
    }

    public function testRemoveMerchandisingItemToRepository(): void
    {
        $repository = $this->createSUT();

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $repository->add($merchandisingItem);

        $recoveredMerchandisingItem = $repository->find($merchandisingItem->id());

        $this->assertNotNull($recoveredMerchandisingItem);
        $this->assertEquals($merchandisingItem->id(), $recoveredMerchandisingItem->id());

        $repository->remove($merchandisingItem);

        $this->assertNull($repository->find($merchandisingItem->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::fromName('Nombre específico'));

        $filter = new MerchandisingItemFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MerchandisingItemFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::fromName('Nombre específico'));

        $filter = new MerchandisingItemFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $repository->add($merchandisingItem);

        $recoveredMerchandisingItem = $repository->find($merchandisingItem->id());

        $this->assertNotNull($recoveredMerchandisingItem);

        $recoveredMerchandisingItem->remove();

        $repository->add($merchandisingItem);

        $this->assertNull($repository->find($merchandisingItem->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $this->assertNull($repository->find($merchandisingItem->id()));

        $merchandisingItem->translate('translated-name', Locale::English);

        $repository->add($merchandisingItem);

        $recoveredMerchandisingItem = $repository->find($merchandisingItem->id());

        $this->assertNotNull($recoveredMerchandisingItem);
        $this->assertEquals($merchandisingItem->id(), $recoveredMerchandisingItem->id());
        $this->assertEquals('translated-name', $recoveredMerchandisingItem->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $merchandisingItem->translate('translated-name', Locale::English);

        $repository->add($merchandisingItem);

        $recoveredMerchandisingItems = $repository->findByFilter(new MerchandisingItemFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredMerchandisingItems);
        $this->assertEquals($merchandisingItem->id(), $recoveredMerchandisingItems[0]->id());
        $this->assertEquals('translated-name', $recoveredMerchandisingItems[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MerchandisingItemRepository;
}
