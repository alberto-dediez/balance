<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAnswered;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionStepStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\TripAnswer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;
use PHPUnit\Framework\TestCase;

class OrganizationalEmployeeContributionTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterStartsPendingWithAToken(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $registered = array_filter($contribution->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeContributionRegistered);
        $this->assertCount(1, $registered);
        $this->assertSame('Employee Name', $contribution->employeeName());
        $this->assertTrue($contribution->status()->isPending());
        $this->assertSame(OrganizationalEmployeeContributionStepStatus::Started, $contribution->stepStatus());
        $this->assertSame(64, strlen($contribution->token()));
    }

    public function testAnswerCommutingReplacesTheCommutesAndStoresTheWorkplace(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();
        $car = MobilityId::generate();
        $train = MobilityId::generate();
        $workplace = PropertyId::generate();

        $contribution->answerCommuting(null, 46, [new CommuteAnswer($car, 12.0, 5, 1)]);
        $contribution->answerCommuting($workplace, 44, [new CommuteAnswer($train, 30.0, 4, 1), new CommuteAnswer($car, 5.0, 1, 2)]);

        $commutes = $contribution->commutes();
        $this->assertCount(2, $commutes);
        $this->assertTrue($train->equals($commutes[0]->mobilityId()));
        $this->assertSame(30.0, $commutes[0]->distanceKm());
        $this->assertSame(4, $commutes[0]->daysPerWeek());
        $this->assertSame(2, $commutes[1]->vehicleOccupants());
        $this->assertSame(44, $contribution->commutingWeeksPerYear());
        $this->assertNotNull($contribution->workplacePropertyId());
        $this->assertTrue($workplace->equals($contribution->workplacePropertyId()));
        $this->assertSame(OrganizationalEmployeeContributionStepStatus::CommutingAnswered, $contribution->stepStatus());
        $this->assertTrue($contribution->status()->isPending());
    }

    public function testAnswerCommutingWithoutCommutesIsValid(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $contribution->answerCommuting(null, null, []);

        $this->assertSame([], $contribution->commutes());
        $this->assertNull($contribution->commutingWeeksPerYear());
        $this->assertSame(OrganizationalEmployeeContributionStepStatus::CommutingAnswered, $contribution->stepStatus());
    }

    public function testAnswerBusinessTravelMarksTheContributionAsAnswered(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();
        $contribution->answerCommuting(null, null, []);

        $contribution->answerBusinessTravel([new TripAnswer(MobilityId::generate(), 1200.0, 1, 1, 2)]);

        $answered = array_filter($contribution->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeContributionAnswered);
        $this->assertCount(1, $answered);
        $this->assertCount(1, $contribution->trips());
        $this->assertSame(2, $contribution->trips()[0]->hotelNights());
        $this->assertTrue($contribution->status()->isAnswered());
        $this->assertNotNull($contribution->answeredAt());
        $this->assertSame(OrganizationalEmployeeContributionStepStatus::Completed, $contribution->stepStatus());
    }

    public function testAnsweringCommutingAgainDoesNotRegressTheStepStatus(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();
        $contribution->answerCommuting(null, null, []);
        $contribution->answerBusinessTravel([]);

        $contribution->answerCommuting(null, null, []);

        $this->assertSame(OrganizationalEmployeeContributionStepStatus::Completed, $contribution->stepStatus());
    }

    /** @return iterable<string, array{0: ?int, 1: CommuteAnswer}> */
    public static function invalidCommutes(): iterable
    {
        yield 'zero days' => [46, new CommuteAnswer(MobilityId::generate(), 12.0, 0, 1)];
        yield 'eight days' => [46, new CommuteAnswer(MobilityId::generate(), 12.0, 8, 1)];
        yield 'zero weeks' => [0, new CommuteAnswer(MobilityId::generate(), 12.0, 5, 1)];
        yield 'fifty four weeks' => [54, new CommuteAnswer(MobilityId::generate(), 12.0, 5, 1)];
        yield 'missing weeks' => [null, new CommuteAnswer(MobilityId::generate(), 12.0, 5, 1)];
        yield 'zero km' => [46, new CommuteAnswer(MobilityId::generate(), 0.0, 5, 1)];
        yield 'negative km' => [46, new CommuteAnswer(MobilityId::generate(), -12.0, 5, 1)];
        yield 'zero occupants' => [46, new CommuteAnswer(MobilityId::generate(), 12.0, 5, 0)];
        yield 'one hundred and one occupants' => [46, new CommuteAnswer(MobilityId::generate(), 12.0, 5, 101)];
    }

    /** @dataProvider invalidCommutes */
    public function testInvalidCommutesThrow(?int $weeksPerYear, CommuteAnswer $commute): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $this->expectException(\InvalidArgumentException::class);

        $contribution->answerCommuting(null, $weeksPerYear, [$commute]);
    }

    /** @return iterable<string, array{0: TripAnswer}> */
    public static function invalidTrips(): iterable
    {
        yield 'zero km' => [new TripAnswer(MobilityId::generate(), 0.0, 1, 1, 0)];
        yield 'zero trips' => [new TripAnswer(MobilityId::generate(), 100.0, 0, 1, 0)];
        yield 'zero occupants' => [new TripAnswer(MobilityId::generate(), 100.0, 1, 0, 0)];
        yield 'negative km' => [new TripAnswer(MobilityId::generate(), -100.0, 1, 1, 0)];
        yield 'negative nights' => [new TripAnswer(MobilityId::generate(), 100.0, 1, 1, -1)];
        yield 'one thousand and one trips' => [new TripAnswer(MobilityId::generate(), 100.0, 1001, 1, 0)];
        yield 'one hundred and one occupants' => [new TripAnswer(MobilityId::generate(), 100.0, 1, 101, 0)];
        yield 'three hundred and sixty seven nights' => [new TripAnswer(MobilityId::generate(), 100.0, 1, 1, 367)];
    }

    /** @dataProvider invalidTrips */
    public function testInvalidTripsThrow(TripAnswer $trip): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $this->expectException(\InvalidArgumentException::class);

        $contribution->answerBusinessTravel([$trip]);
    }

    public function testTheUpperLimitsAreAccepted(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $contribution->answerCommuting(null, 53, [new CommuteAnswer(MobilityId::generate(), 12.0, 7, 100)]);
        $contribution->answerBusinessTravel([new TripAnswer(MobilityId::generate(), 100.0, 1000, 100, 366)]);

        $this->assertTrue($contribution->status()->isAnswered());
    }

    public function testIgnoreAndUnignoreWithoutAnswerReturnsToPending(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $contribution->ignore();
        $this->assertTrue($contribution->status()->isIgnored());
        $this->assertNotNull($contribution->ignoredAt());

        $contribution->unignore();
        $this->assertTrue($contribution->status()->isPending());
        $this->assertNull($contribution->ignoredAt());
    }

    public function testUnignoreAnAnsweredContributionReturnsToAnswered(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();
        $contribution->answerCommuting(null, null, []);
        $contribution->answerBusinessTravel([]);

        $contribution->ignore();
        $contribution->unignore();

        $this->assertTrue($contribution->status()->isAnswered());
    }

    public function testResendNotificationPublishesTheEvent(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();

        $contribution->resendNotification();

        $resent = array_filter($contribution->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeContributionNotificationResent);
        $this->assertCount(1, $resent);
    }

    public function testGrantConsent(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::registered();
        $this->assertFalse($contribution->hasConsent());

        $contribution->grantConsent(ConsentProofMother::granted());

        $this->assertTrue($contribution->hasConsent());
    }
}
