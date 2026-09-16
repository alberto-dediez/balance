<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineEventCarbonFootprintRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineEventCarbonFootprintActivitiesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testActivitiesSurviveReload(): void
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);
        $eventCarbonFootprintRepository = new DoctrineEventCarbonFootprintRepository($manager);
        $activityRepository = new DoctrineActivityRepository($manager);

        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $activity = MaterialActivityMother::registered();
        $activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), new ActivityPlacement(Phase::Materials, Source::Materials));
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $manager->clear();

        $reloaded = $eventCarbonFootprintRepository->find($eventCarbonFootprint->id());
        $this->assertNotNull($reloaded);

        $activityIds = $reloaded->activities()->filterByPhase(Phase::Materials)->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activity->id()->equals($activityIds[0]));

        $reloadedActivity = $activityRepository->findById($activityIds[0]);
        $this->assertInstanceOf(MaterialActivity::class, $reloadedActivity);
        $this->assertSame(10.0, $reloadedActivity->quantity()->value());
    }
}
