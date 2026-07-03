<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class OvernightStayRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddOvernightStayToRepository(): void
    {
        $repository = $this->createSUT();

        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $this->assertNull($repository->find($overnightStay->id()));

        $repository->add($overnightStay);

        $recoveredOvernightStay = $repository->find($overnightStay->id());

        $this->assertNotNull($recoveredOvernightStay);
        $this->assertEquals($overnightStay->id(), $recoveredOvernightStay->id());
    }

    public function testRemoveOvernightStayToRepository(): void
    {
        $repository = $this->createSUT();

        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $repository->add($overnightStay);

        $recoveredOvernightStay = $repository->find($overnightStay->id());

        $this->assertNotNull($recoveredOvernightStay);
        $this->assertEquals($overnightStay->id(), $recoveredOvernightStay->id());

        $repository->remove($overnightStay);

        $this->assertNull($repository->find($overnightStay->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::fromName('Nombre específico'));

        $filter = new OvernightStayFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);

        $filter = new OvernightStayFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::fromName('Nombre específico'));

        $filter = new OvernightStayFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $repository->add($overnightStay);

        $recoveredOvernightStay = $repository->find($overnightStay->id());

        $this->assertNotNull($recoveredOvernightStay);

        $recoveredOvernightStay->remove();

        $repository->add($overnightStay);

        $this->assertNull($repository->find($overnightStay->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $this->assertNull($repository->find($overnightStay->id()));

        $overnightStay->translate('translated-name', Locale::English);

        $repository->add($overnightStay);

        $recoveredOvernightStay = $repository->find($overnightStay->id());

        $this->assertNotNull($recoveredOvernightStay);
        $this->assertEquals($overnightStay->id(), $recoveredOvernightStay->id());
        $this->assertEquals('translated-name', $recoveredOvernightStay->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): OvernightStayRepository;
}
