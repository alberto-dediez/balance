<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\MaterialAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CateringItemAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MaterialAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerPhaseServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private Material $material;
    private AnswerPhaseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->material = MaterialMother::registeredMaterial();
        $this->material->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $materialRepository = MaterialRepositoryMother::inMemory();
        $materialRepository->add($this->material);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
        $materialSelector = new MaterialEmissionFactorSelector($materialRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new MaterialAnswerHandler(
                new RegisterMaterialActivityService($this->activityRepository, $authorizer, $materialSelector, $calculator),
                new UpdateMaterialActivityService($this->activityRepository, $authorizer, $materialSelector, $calculator),
            ),
        ]);

        $this->sut = new AnswerPhaseService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            new PhaseAnswersSynchronizer($handlers),
        );
    }

    public function testAnswersMaterialsAndRegistersTheActivity(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$this->materialsSection()]), $this->ownerUser());

        $this->assertSame(EventCarbonFootprintStatus::MaterialsAnswered, $eventCarbonFootprint->status());

        $activityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds();
        $this->assertCount(1, $activityIds);

        $activity = $this->activityRepository->findById($activityIds[0]);
        $this->assertInstanceOf(MaterialActivity::class, $activity);
    }

    public function testEventCarbonFootprintNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new AnswerPhaseRequest(EventCarbonFootprintMother::random()->id(), Phase::Materials), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testSectionThatDoesNotBelongToThePhaseThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $foreignSection = new SectionAnswer(Phase::Assembly->sections()[0], []);

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$foreignSection]), $this->ownerUser());
    }

    public function testAnswerWithMismatchingTypeThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $mismatchedSection = new SectionAnswer(
            Phase::Materials->sections()[1],
            [new MaterialAnswer($this->material->id(), ItemCondition::New, new Quantity(10.0, Unit::Kilogram))],
        );

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$mismatchedSection]), $this->ownerUser());
    }

    public function testATamperedSectionDefinitionIsReplacedByTheCanonicalOneBeforeSyncing(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $tamperedSection = new SectionAnswer(
            new SectionDefinition('materials', Source::WasteManagement, ActivityType::Material),
            [new MaterialAnswer($this->material->id(), ItemCondition::New, new Quantity(10.0, Unit::Kilogram))],
        );

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$tamperedSection]), $this->ownerUser());

        $this->assertCount(0, $eventCarbonFootprint->activities()->filterBySource(Source::WasteManagement)->activityIds());
        $this->assertCount(1, $eventCarbonFootprint->activities()->filterBySource(Source::Materials)->activityIds());
    }

    public function testAnAnswerNotMatchingTheCanonicalDefinitionTypeIsRejectedEvenIfItMatchesTheTamperedOne(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $tamperedSection = new SectionAnswer(
            new SectionDefinition('materials', Source::Materials, ActivityType::CateringItem),
            [new CateringItemAnswer(CateringItemId::generate(), ItemCondition::New, new Quantity(10.0, Unit::Kilogram))],
        );

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$tamperedSection]), $this->ownerUser());
    }

    public function testCelebrationAndBreakdownJustAdvanceTheStatus(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Celebration), $this->ownerUser());
        $this->assertSame(EventCarbonFootprintStatus::CelebrationAnswered, $eventCarbonFootprint->status());

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Breakdown), $this->ownerUser());
        $this->assertSame(EventCarbonFootprintStatus::BreakdownAnswered, $eventCarbonFootprint->status());
    }

    public function testAnExclusionIsRegisteredAndRemovesThePreviousActivitiesOfThatSource(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$this->materialsSection()]), $this->ownerUser());
        $this->assertCount(1, $eventCarbonFootprint->activities()->filterBySource(Source::Materials)->activityIds());

        ($this->sut)(
            new AnswerPhaseRequest(
                $eventCarbonFootprint->id(),
                Phase::Materials,
                [],
                [new ExclusionAnswer(Source::Materials, ExclusionReason::NoDataAvailable, 'no invoices yet')],
            ),
            $this->ownerUser(),
        );

        $exclusion = $eventCarbonFootprint->exclusions()->forPhaseAndSource(Phase::Materials, Source::Materials);
        $this->assertNotNull($exclusion);
        $this->assertSame(ExclusionReason::NoDataAvailable, $exclusion->reason());
        $this->assertSame('no invoices yet', $exclusion->comment());
        $this->assertCount(0, $eventCarbonFootprint->activities()->filterBySource(Source::Materials)->activityIds());
    }

    public function testAnswersForAnExcludedSourceThrow(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(
            new AnswerPhaseRequest(
                $eventCarbonFootprint->id(),
                Phase::Materials,
                [$this->materialsSection()],
                [new ExclusionAnswer(Source::Materials, ExclusionReason::NotApplicable)],
            ),
            $this->ownerUser(),
        );
    }

    public function testAnExclusionOfASourceForeignToThePhaseThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(
            new AnswerPhaseRequest(
                $eventCarbonFootprint->id(),
                Phase::Materials,
                [],
                [new ExclusionAnswer(Source::WasteManagement, ExclusionReason::NotApplicable)],
            ),
            $this->ownerUser(),
        );
    }

    public function testOmittingAPreviousExclusionReIncludesTheSource(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(
            new AnswerPhaseRequest(
                $eventCarbonFootprint->id(),
                Phase::Materials,
                [],
                [new ExclusionAnswer(Source::Materials, ExclusionReason::NotSignificant)],
            ),
            $this->ownerUser(),
        );
        $this->assertTrue($eventCarbonFootprint->exclusions()->isExcluded(Phase::Materials, Source::Materials));

        ($this->sut)(new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Materials, [$this->materialsSection()]), $this->ownerUser());

        $this->assertFalse($eventCarbonFootprint->exclusions()->isExcluded(Phase::Materials, Source::Materials));
        $this->assertCount(1, $eventCarbonFootprint->activities()->filterBySource(Source::Materials)->activityIds());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new AnswerPhaseRequest(EventCarbonFootprintMother::random()->id(), Phase::Materials)));
    }

    private function materialsSection(): SectionAnswer
    {
        return new SectionAnswer(
            Phase::Materials->sections()[0],
            [new MaterialAnswer($this->material->id(), ItemCondition::New, new Quantity(10.0, Unit::Kilogram))],
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
