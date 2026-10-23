<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\Export\EventAttendeeSurveysExportFilter;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Infrastructure\DataExchange\EventAttendeeSurveysExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EventAttendeeSurveysExportationTypeTest extends TestCase
{
    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;

    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private InMemoryProjectRepository $projectRepository;

    private InMemoryEventRepository $eventRepository;

    private RequestStack $requestStack;

    private EventAttendeeSurveysExportationType $sut;

    protected function setUp(): void
    {
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();
        $this->projectRepository = new InMemoryProjectRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->requestStack = new RequestStack();

        $this->sut = new EventAttendeeSurveysExportationType(
            $this->attendeeSurveyResponseRepository,
            $this->attendeeSurveyRepository,
            new OriginCompensationsCalculator($this->projectContributionRepository, $this->projectRepository),
            new EventAuthorizer(new StubCurrentAccountIdProvider(new AccountId('account-id'))),
            $this->eventRepository,
            new FakeTranslator(),
            new StubCurrentAccountIdProvider(new AccountId('account-id')),
            $this->requestStack,
        );
    }

    public function testCreatesAnEmptyFilterWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(EventAttendeeSurveysExportFilter::class, $filter);
        self::assertNull($filter->eventId);
    }

    public function testPreselectsTheEventFromTheQueryString(): void
    {
        $eventId = (string) EventId::generate();

        $this->requestStack->push(Request::create(
            '/event_attendee_surveys/export',
            parameters: ['eventId' => $eventId],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(EventAttendeeSurveysExportFilter::class, $filter);
        self::assertSame($eventId, (string) $filter->eventId);
        self::assertNull($filter->from);
        self::assertNull($filter->to);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $eventId = EventId::generate();

        $criteria = $this->sut->dehydrateFilter(new EventAttendeeSurveysExportFilter(
            eventId: $eventId,
            from: new \DateTimeImmutable('2026-01-01'),
            to: new \DateTimeImmutable('2026-01-31'),
        ));

        self::assertSame(
            ['eventId' => (string) $eventId, 'from' => '2026-01-01', 'to' => '2026-01-31'],
            $criteria,
        );

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(EventAttendeeSurveysExportFilter::class, $filter);
        self::assertSame((string) $eventId, (string) $filter->eventId);
        self::assertSame('2026-01-01', $filter->from?->format('Y-m-d'));
        self::assertSame('2026-01-31', $filter->to?->format('Y-m-d'));
    }

    public function testDehydratesAnEmptyFilterWithoutNulls(): void
    {
        $criteria = $this->sut->dehydrateFilter(new EventAttendeeSurveysExportFilter());

        self::assertSame(['eventId' => null, 'from' => null, 'to' => null], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(EventAttendeeSurveysExportFilter::class, $filter);
        self::assertNull($filter->eventId);
    }

    public function testYieldsNoRowsWhenTheEventHasNoSurvey(): void
    {
        $rows = $this->sut->rows(new EventAttendeeSurveysExportFilter(eventId: EventId::generate()), []);

        self::assertSame([], iterator_to_array($rows));
    }

    public function testYieldsTheResponsesOfTheSurvey(): void
    {
        $eventId = EventId::generate();
        $survey = AttendeeSurveyMother::fromEventId((string) $eventId);
        $this->attendeeSurveyRepository->add($survey);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::from(
                attendeeSurveyId: (string) $survey->id(),
                cameFromAnotherCity: true,
                intercityRoundTrip: true,
                intercityTrips: 2,
                usedHotel: true,
                hotelNights: 3,
            ),
        );

        $rows = iterator_to_array($this->sut->rows(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            [],
        ));

        self::assertCount(1, $rows);
        self::assertSame('Sí', $rows[0]['cameFromAnotherCity']);
        self::assertSame('Sí', $rows[0]['usedHotel']);
        self::assertSame(3, $rows[0]['hotelNights']);
        self::assertSame('Sí', $rows[0]['intercityRoundTrip']);
        self::assertSame(2, $rows[0]['intercityTrips']);
        self::assertSame('No', $rows[0]['movedWithinCity']);
    }

    public function testIncludesTheResponsesOfTheLastDayOfTheRange(): void
    {
        $eventId = EventId::generate();
        $survey = AttendeeSurveyMother::fromEventId((string) $eventId);
        $this->attendeeSurveyRepository->add($survey);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $survey->id()),
        );

        $today = new \DateTimeImmutable('today');

        $rows = iterator_to_array($this->sut->rows(
            new EventAttendeeSurveysExportFilter(eventId: $eventId, from: $today, to: $today),
            [],
        ));

        self::assertCount(1, $rows);
    }

    public function testDiscardsTheResponsesOutOfTheRange(): void
    {
        $eventId = EventId::generate();
        $survey = AttendeeSurveyMother::fromEventId((string) $eventId);
        $this->attendeeSurveyRepository->add($survey);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $survey->id()),
        );

        $rows = iterator_to_array($this->sut->rows(
            new EventAttendeeSurveysExportFilter(
                eventId: $eventId,
                from: new \DateTimeImmutable('tomorrow'),
            ),
            [],
        ));

        self::assertSame([], $rows);
    }

    public function testReportsTheCompensationOfEachResponse(): void
    {
        $eventId = EventId::generate();
        $survey = AttendeeSurveyMother::fromEventId((string) $eventId);
        $this->attendeeSurveyRepository->add($survey);

        $compensated = AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $survey->id());
        $this->attendeeSurveyResponseRepository->add($compensated);

        $projectId = ProjectId::generate();
        $this->projectRepository->add(ProjectMother::fromNameAndId('Bosque de La Rioja', (string) $projectId));

        $this->projectContributionRepository->add(ProjectContributionMother::paidFromAttendeeSurveyResponse(
            new AccountId('account-id'),
            $projectId,
            (string) $compensated->id(),
            tonnes: 0.25,
        ));

        $rows = iterator_to_array($this->sut->rows(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            [],
        ));

        self::assertCount(1, $rows);
        self::assertSame('Sí', $rows[0]['compensated']);
        self::assertSame('Bosque de La Rioja', $rows[0]['project']);
        self::assertSame(6.05, $rows[0]['contributionAmount']);
        self::assertSame(250.0, $rows[0]['compensatedEmissions']);
    }

    public function testDoesNotReportPendingContributionsAsCompensated(): void
    {
        $eventId = EventId::generate();
        $survey = AttendeeSurveyMother::fromEventId((string) $eventId);
        $this->attendeeSurveyRepository->add($survey);

        $response = AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $survey->id());
        $this->attendeeSurveyResponseRepository->add($response);

        $this->projectContributionRepository->add(ProjectContributionMother::pendingFromAttendeeSurveyResponse(
            new AccountId('account-id'),
            ProjectId::generate(),
            (string) $response->id(),
        ));

        $rows = iterator_to_array($this->sut->rows(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            [],
        ));

        self::assertSame('No', $rows[0]['compensated']);
        self::assertSame('', $rows[0]['compensatedEmissions']);
    }

    public function testOnlyOwnersOfTheCurrentAccountAreAuthorized(): void
    {
        self::assertTrue($this->sut->isAuthorized(
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));

        self::assertFalse($this->sut->isAuthorized(UserMother::adminUser()));
    }

    public function testAuthorizesTheOwnerToExportAnEventOfTheCurrentAccount(): void
    {
        $eventId = EventId::generate();
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Congreso', 'account-id', (string) $eventId));

        self::assertTrue($this->sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAnEventOfAnotherAccount(): void
    {
        $eventId = EventId::generate();
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Congreso', 'other-account', (string) $eventId));

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testAuthorizesTheOwnerToExportAnEventOfTheImpersonatedAccount(): void
    {
        $sut = $this->sutWithCurrentAccount(new AccountId('impersonated'));
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        $impersonatedEventId = EventId::generate();
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Congreso', 'impersonated', (string) $impersonatedEventId));

        $ownEventId = EventId::generate();
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Jornada', 'account-id', (string) $ownEventId));

        self::assertTrue($sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: $impersonatedEventId),
            $owner,
        ));

        self::assertFalse($sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: $ownEventId),
            $owner,
        ));
    }

    public function testDoesNotAuthorizeAFilterWithoutEvent(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAnUnknownEvent(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: EventId::generate()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAdminsToExportEventSurveys(): void
    {
        $eventId = EventId::generate();
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Congreso', 'account-id', (string) $eventId));

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventAttendeeSurveysExportFilter(eventId: $eventId),
            UserMother::adminUser(),
        ));
    }

    public function testDoesNotAuthorizeAnotherFilterClass(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(
            new \stdClass(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    private function sutWithCurrentAccount(AccountId $currentAccountId): EventAttendeeSurveysExportationType
    {
        return new EventAttendeeSurveysExportationType(
            $this->attendeeSurveyResponseRepository,
            $this->attendeeSurveyRepository,
            new OriginCompensationsCalculator($this->projectContributionRepository, $this->projectRepository),
            new EventAuthorizer(new StubCurrentAccountIdProvider($currentAccountId)),
            $this->eventRepository,
            new FakeTranslator(),
            new StubCurrentAccountIdProvider($currentAccountId),
            $this->requestStack,
        );
    }
}
