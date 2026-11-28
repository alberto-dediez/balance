<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityRequest;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotAcceptingEntriesException;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryCountryNotAllowedException;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class CheckContestEntryEligibilityServiceTest extends ContestApplicationTestCase
{
    public function testEligibleReturnsTheContest(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $response = $this->eligibility()($this->request($contest));

        $this->assertTrue($response->contest->id()->equals($contest->id()));
        $this->assertNull($response->pendingEntry);
    }

    public function testOutOfPeriod(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, startsAt: new \DateTimeImmutable('-10 days'), endsAt: new \DateTimeImmutable('-1 day')));

        $this->expectException(ContestNotAcceptingEntriesException::class);

        $this->eligibility()($this->request($contest));
    }

    public function testFeatureDisabled(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT_WITHOUT_CONTESTS));

        $this->expectException(ContestNotAcceptingEntriesException::class);

        $this->eligibility()($this->request($contest));
    }

    public function testRemovedCalculator(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [ContestMother::eventHost('kept')]));

        $this->expectException(ContestNotAcceptingEntriesException::class);

        $this->eligibility()(new CheckContestEntryEligibilityRequest($contest->id(), ContestMother::eventHost('removed'), ProjectContributionOrigin::AttendeeSurveyResponse, 'calc', 'a@example.com', 'ES', true, true));
    }

    public function testWithoutConsent(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->expectException(\InvalidArgumentException::class);

        $this->eligibility()($this->request($contest, consent: false));
    }

    public function testWithoutAdultConfirmation(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, adultsOnly: true));

        $this->expectException(\InvalidArgumentException::class);

        $this->eligibility()($this->request($contest, adult: false));
    }

    public function testCountryNotAllowed(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, allowedCountries: ['PT']));

        $this->expectException(ContestEntryCountryNotAllowedException::class);

        $this->eligibility()($this->request($contest, country: 'FR'));
    }

    public function testDuplicateByOrigin(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $this->storeEntry(ContestEntryMother::confirmed($contest, 1, email: 'otro@example.com', originId: 'calc-1'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->eligibility()($this->request($contest, originId: 'calc-1'));
    }

    public function testDuplicateByEmail(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $this->storeEntry(ContestEntryMother::confirmed($contest, 1, email: 'ana@example.com', originId: 'calc-9'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->eligibility()($this->request($contest, originId: 'calc-1'));
    }

    public function testPendingEntryOfTheSameCalculationIsNotADuplicate(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, participationMode: ContestParticipationMode::Compensation));
        $pending = $this->storeEntry(ContestEntryMother::pending($contest, email: 'ana@example.com', originId: 'calc-1'));

        $response = $this->eligibility()($this->request($contest, originId: 'calc-1'));

        $this->assertTrue($response->pendingEntry?->id()->equals($pending->id()));
    }

    public function testDisqualifiedEmailIsStillTaken(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = ContestEntryMother::confirmed($contest, 1, email: 'ana@example.com', originId: 'calc-9');
        $entry->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $this->storeEntry($entry);

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->eligibility()($this->request($contest, originId: 'calc-1'));
    }

    private function request(Contest $contest, string $originId = 'calc-1', string $country = 'ES', bool $consent = true, bool $adult = true): CheckContestEntryEligibilityRequest
    {
        return new CheckContestEntryEligibilityRequest(
            contestId: $contest->id(),
            host: $contest->hostReferences()[0],
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $originId,
            email: 'ana@example.com',
            country: $country,
            consentAccepted: $consent,
            adultConfirmed: $adult,
        );
    }
}
