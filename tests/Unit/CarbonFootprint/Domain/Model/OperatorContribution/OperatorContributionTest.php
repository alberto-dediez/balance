<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionActivityAdded;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionActivityRemoved;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionAnswered;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionContactUpdated;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMarkedAsPrecalculated;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRegistered;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionUnmarkedAsPrecalculated;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionUserLinked;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class OperatorContributionTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegister(): void
    {
        $contribution = OperatorContributionMother::pending(source: Source::DownstreamLeasedAssets, token: str_repeat('a', 64), emissionFactorGroupId: 'group-id');

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertSame(Source::DownstreamLeasedAssets, $contribution->source());
        $this->assertSame('operador@hotel.es', $contribution->contactEmail());
        $this->assertSame(Locale::Spanish, $contribution->contactLocale());
        $this->assertSame(2025, $contribution->calculationYear());
        $this->assertSame('group-id', (string) $contribution->emissionFactorGroupId());
        $this->assertSame(str_repeat('a', 64), $contribution->token());
        $this->assertFalse($contribution->isPrecalculated());
        $this->assertFalse($contribution->isCompleted());
        $this->assertFalse($contribution->hasConsent());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(OperatorContributionRegistered::class, $lastEvent);

        $rebuilt = OperatorContributionRegistered::rebuildFromPayload($lastEvent->id(), $lastEvent->occurredOn(), $lastEvent->payload());
        $this->assertSame($lastEvent->payload(), $rebuilt->payload());
    }

    public function testRegisterWithEmptyEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending(contactEmail: '');
    }

    public function testRegisterWithInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending(contactEmail: 'not-an-email');
    }

    public function testRegisterWithNotAdmittedSourceThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending(source: Source::FixedFacilities);
    }

    public function testRegisterWithEmptyTokenThrows(): void
    {
        $contribution = new OperatorContribution(id: OperatorContributionId::generate());

        $this->expectException(\InvalidArgumentException::class);

        $contribution->register(
            companyCarbonFootprintId: CompanyCarbonFootprintId::generate(),
            accountId: AccountId::generate(),
            propertyId: PropertyId::generate(),
            source: Source::Franchises,
            year: 2025,
            emissionFactorGroupId: null,
            contactEmail: 'operador@hotel.es',
            contactName: null,
            contactLocale: Locale::Spanish,
            token: '',
        );
    }

    public function testRegisterWithTooLongContactNameThrows(): void
    {
        $contribution = new OperatorContribution(id: OperatorContributionId::generate());

        $this->expectException(\InvalidArgumentException::class);

        $contribution->register(
            companyCarbonFootprintId: CompanyCarbonFootprintId::generate(),
            accountId: AccountId::generate(),
            propertyId: PropertyId::generate(),
            source: Source::Franchises,
            year: 2025,
            emissionFactorGroupId: null,
            contactEmail: 'operador@hotel.es',
            contactName: str_repeat('a', 256),
            contactLocale: Locale::Spanish,
            token: OperatorContribution::generateToken(),
        );
    }

    public function testLinkUser(): void
    {
        $contribution = OperatorContributionMother::pending();

        $contribution->linkUser(new UserId('user-id'));

        $this->assertSame('user-id', (string) $contribution->userId());
        $this->assertInstanceOf(OperatorContributionUserLinked::class, $this->spySubscriber->lastEvent());
    }

    public function testGrantConsent(): void
    {
        $contribution = OperatorContributionMother::withConsent(OperatorContributionMother::pending());

        $this->assertTrue($contribution->hasConsent());
    }

    public function testUpdateContactNameOnlyPublishesWhenItChanges(): void
    {
        $contribution = OperatorContributionMother::pending();

        $contribution->updateContactName('Ana');

        $this->assertSame('Ana', $contribution->contactName());
        $this->assertInstanceOf(OperatorContributionContactUpdated::class, $this->spySubscriber->lastEvent());

        $eventsBefore = count($this->spySubscriber->events());
        $contribution->updateContactName(' Ana ');

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testAddActivity(): void
    {
        $contribution = OperatorContributionMother::pending();
        $activityId = ActivityId::generate();
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $contribution->addActivity($activityId, $placement);
        $contribution->addActivity($activityId, $placement);

        $this->assertCount(1, $contribution->activityIds($placement));
        $this->assertCount(1, $contribution->activityReferences());
        $this->assertTrue($contribution->activityReferences()[0]->placement->equals($placement));
        $this->assertInstanceOf(OperatorContributionActivityAdded::class, $this->spySubscriber->lastEvent());
    }

    public function testAddActivityOfScope3Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::Franchises));
    }

    public function testAddActivityWithProviderThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, providerId: new ProviderId('provider-id')));
    }

    public function testAddActivityWithPropertyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-id')));
    }

    public function testAddActivityFromEmployeesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, fromEmployees: true));
    }

    public function testAddActivityWithSourceOutsideTheScopeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope2, Source::FixedFacilities));
    }

    public function testRetainActivities(): void
    {
        $kept = ActivityId::generate();
        $dropped = ActivityId::generate();
        $contribution = OperatorContributionMother::withActivities([$kept, $dropped]);
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $contribution->retainActivities($placement, [$kept]);

        $this->assertCount(1, $contribution->activityIds($placement));
        $this->assertTrue($kept->equals($contribution->activityIds($placement)[0]));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(OperatorContributionActivityRemoved::class, $lastEvent);
        $this->assertTrue($dropped->equals($lastEvent->activityId()));
        $this->assertSame(Scope::Scope1, $lastEvent->scope());
        $this->assertSame(Source::FixedFacilities, $lastEvent->source());
    }

    public function testRemoveAllActivities(): void
    {
        $contribution = OperatorContributionMother::withActivities([ActivityId::generate(), ActivityId::generate()]);

        $contribution->removeAllActivities();

        $this->assertSame([], $contribution->activityReferences());
    }

    public function testMarkAsPrecalculatedRemovesTheActivitiesAndIsIdempotent(): void
    {
        $contribution = OperatorContributionMother::withActivities([ActivityId::generate()]);

        $contribution->markAsPrecalculated();

        $this->assertTrue($contribution->isPrecalculated());
        $this->assertSame([], $contribution->activityReferences());

        $markedEvents = array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionMarkedAsPrecalculated);
        $this->assertCount(1, $markedEvents);
        $this->assertInstanceOf(OperatorContributionActivityRemoved::class, $this->spySubscriber->lastEvent());

        $eventsBefore = count($this->spySubscriber->events());
        $contribution->markAsPrecalculated();

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testUnmarkAsPrecalculatedRemovesTheActivities(): void
    {
        $contribution = OperatorContributionMother::precalculated();
        $contribution->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $contribution->unmarkAsPrecalculated();

        $this->assertFalse($contribution->isPrecalculated());
        $this->assertSame([], $contribution->activityReferences());
        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionUnmarkedAsPrecalculated));
    }

    public function testMarkingAnAnsweredContributionAsPrecalculatedReturnsItToPending(): void
    {
        $contribution = OperatorContributionMother::answered();

        $contribution->markAsPrecalculated();

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->answeredAt());
    }

    public function testUnmarkingAnAnsweredContributionAsPrecalculatedReturnsItToPending(): void
    {
        $contribution = OperatorContributionMother::precalculated();
        $contribution->answer();

        $contribution->unmarkAsPrecalculated();

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->answeredAt());
    }

    public function testChangingTheModeOfAnIgnoredContributionKeepsItIgnoredAndUnignoresToPending(): void
    {
        $contribution = OperatorContributionMother::answered();
        $contribution->ignore();

        $contribution->markAsPrecalculated();

        $this->assertSame(ProviderContributionStatus::Ignored, $contribution->status());
        $this->assertNull($contribution->answeredAt());

        $contribution->unignore();

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
    }

    public function testSectionsForDependOnThePrecalculatedMark(): void
    {
        $contribution = OperatorContributionMother::pending();

        $keys = static fn(array $sections): array => array_map(static fn(SectionDefinition $section): string => $section->key, $sections);

        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], $keys($contribution->sectionsFor(Scope::Scope1)));
        $this->assertSame(['electricity'], $keys($contribution->sectionsFor(Scope::Scope2)));

        $contribution->markAsPrecalculated();

        $this->assertSame(['fixed_facilities_reported', 'own_vehicles_reported', 'refrigerant_leaks_reported'], $keys($contribution->sectionsFor(Scope::Scope1)));
        $this->assertSame(['building_electricity_reported'], $keys($contribution->sectionsFor(Scope::Scope2)));
    }

    public function testSectionsForScope3Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OperatorContributionMother::pending()->sectionsFor(Scope::Scope3);
    }

    public function testAnswerIsIdempotent(): void
    {
        $contribution = OperatorContributionMother::pending();

        $contribution->answer();
        $contribution->answer();

        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
        $this->assertNotNull($contribution->answeredAt());
        $this->assertCount(1, array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionAnswered));
    }

    public function testIgnoreAndUnignoreAPendingContribution(): void
    {
        $contribution = OperatorContributionMother::pending();

        $contribution->ignore();

        $this->assertSame(ProviderContributionStatus::Ignored, $contribution->status());
        $this->assertNotNull($contribution->ignoredAt());

        $contribution->unignore();

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->ignoredAt());
    }

    public function testUnignoreAnAnsweredContributionReturnsToAnswered(): void
    {
        $contribution = OperatorContributionMother::answered();

        $contribution->ignore();
        $contribution->unignore();

        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
    }

    public function testResendAnIgnoredContributionThrows(): void
    {
        $contribution = OperatorContributionMother::ignored();

        $this->expectException(\LogicException::class);

        $contribution->resendNotification();
    }

    public function testRemove(): void
    {
        $contribution = OperatorContributionMother::pending();

        $contribution->remove();

        $this->assertTrue($contribution->isRemoved());
        $this->assertNotNull($contribution->removedAt());
    }

    public function testExclusionsAreNotAdmitted(): void
    {
        $contribution = OperatorContributionMother::pending();

        $this->assertSame([], $contribution->exclusionsFor(Scope::Scope1));
        $this->assertSame([], $contribution->allExclusions());
        $this->assertFalse($contribution->isStageExcluded(Scope::Scope1));
        $this->assertNull($contribution->stageExclusionReason(Scope::Scope1));
        $this->assertNull($contribution->stageExclusionComment(Scope::Scope1));

        $this->expectException(\LogicException::class);

        $contribution->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::cases()[0]);
    }
}
