<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MitigationActionRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMitigationActionToRepository(): void
    {
        $repository = $this->createSUT();

        $action = MitigationActionMother::manual();

        $this->assertNull($repository->find($action->id()));

        $repository->add($action);

        $recoveredAction = $repository->find($action->id());

        $this->assertNotNull($recoveredAction);
        $this->assertEquals($action->id(), $recoveredAction->id());
    }

    public function testRemoveMitigationActionFromRepository(): void
    {
        $repository = $this->createSUT();

        $action = MitigationActionMother::manual();
        $repository->add($action);

        $repository->remove($action);

        $this->assertNull($repository->find($action->id()));
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $action = MitigationActionMother::manual();
        $repository->add($action);

        $action->remove();
        $repository->add($action);

        $this->assertNull($repository->find($action->id()));
        $this->assertCount(0, $repository->findByFilter(new MitigationActionFilter(limit: 0)));
    }

    public function testFindByFilterByCarbonFootprint(): void
    {
        $repository = $this->createSUT();

        $repository->add(MitigationActionMother::manual(carbonFootprintId: 'footprint-1'));
        $repository->add(MitigationActionMother::manual(carbonFootprintId: 'footprint-1'));
        $repository->add(MitigationActionMother::manual(carbonFootprintId: 'footprint-2'));
        $repository->add(MitigationActionMother::manual(carbonFootprintId: 'footprint-1', subjectType: CarbonFootprintReportSubjectType::Company));

        $filter = new MitigationActionFilter(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: 'footprint-1',
            limit: 0,
        );

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
        $this->assertCount(4, $repository->findByFilter(new MitigationActionFilter(limit: 0)));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $action = MitigationActionMother::withTranslation(Locale::English, 'translated-name');
        $repository->add($action);

        $recoveredAction = $repository->find($action->id());

        $this->assertNotNull($recoveredAction);
        $this->assertSame('translated-name', $recoveredAction->name());
    }

    public function testFindRecoverEffects(): void
    {
        $repository = $this->createSUT();

        $action = MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2);
        $repository->add($action);

        $recoveredAction = $repository->find($action->id());

        $this->assertNotNull($recoveredAction);
        $this->assertSame(0.2, $recoveredAction->effects()->forSourceAndItem(Source::FixedFacilities, 'natural-gas')?->reduction());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MitigationActionRepository;
}
