<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryService;
use App\CarbonFootprint\Application\ContestEntry\Register\RegisterContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Register\RegisterContestEntryService;
use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotAcceptingEntriesException;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryCountryNotAllowedException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class RegisterContestEntryServiceTest extends ContestApplicationTestCase
{
    public function testCalculationEntryIsConfirmedAtOnce(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $entry = $this->createSUT()($this->request($contest))->entry;

        $this->assertTrue($entry->isConfirmed());
        $this->assertSame(1, $entry->entryNumber());
        $this->assertNull($entry->projectContributionId());
        $this->assertSame([(string) $contest->id()], $this->contestLock->acquired());
    }

    public function testCompensationEntryStaysPendingPayment(): void
    {
        $contest = $this->compensationContest();

        $entry = $this->createSUT()($this->request($contest, projectContributionId: 'pc-1'))->entry;

        $this->assertTrue($entry->isPendingPayment());
        $this->assertNull($entry->entryNumber());
        $this->assertSame('pc-1', $entry->projectContributionId());
    }

    public function testCompensationWithoutContributionFails(): void
    {
        $contest = $this->compensationContest();

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request($contest));
    }

    public function testOutOfPeriodFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, startsAt: new \DateTimeImmutable('+1 day'), endsAt: new \DateTimeImmutable('+2 days')));

        $this->expectException(ContestNotAcceptingEntriesException::class);

        $this->createSUT()($this->request($contest));
    }

    public function testFeatureDisabledFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT_WITHOUT_CONTESTS));

        $this->expectException(ContestNotAcceptingEntriesException::class);

        $this->createSUT()($this->request($contest));
    }

    public function testWithoutConsentFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request($contest, consent: false));
    }

    public function testWithoutAdultConfirmationFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, adultsOnly: true));

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request($contest, adult: false));
    }

    public function testCountryNotAllowedFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, allowedCountries: ['PT']));

        $this->expectException(ContestEntryCountryNotAllowedException::class);

        $this->createSUT()($this->request($contest, country: 'ES'));
    }

    public function testDuplicateByOriginFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'a@example.com'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'b@example.com'));
    }

    public function testDuplicateByEmailFails(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'a@example.com'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->createSUT()($this->request($contest, originId: 'calc-2', email: 'A@example.com'));
    }

    public function testRetryFromTheSameCalculationRenewsThePendingEntry(): void
    {
        $contest = $this->compensationContest();
        $first = $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'a@example.com', projectContributionId: 'pc-1'))->entry;

        $second = $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'nuevo@example.com', projectContributionId: 'pc-2'))->entry;

        $this->assertTrue($first->id()->equals($second->id()));
        $this->assertSame('pc-2', $second->projectContributionId());
        $this->assertSame('nuevo@example.com', $second->email());
        $this->assertSame(1, $this->contestEntryRepository->countByFilter(new ContestEntryFilter(contestId: $contest->id())));
    }

    public function testEmailOfAPendingEntryFromAnotherCalculationIsTaken(): void
    {
        $contest = $this->compensationContest();
        $this->createSUT()($this->request($contest, originId: 'calc-1', email: 'a@example.com', projectContributionId: 'pc-1'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $this->createSUT()($this->request($contest, originId: 'calc-2', email: 'a@example.com', projectContributionId: 'pc-2'));
    }

    public function testAfterRetryOnlyTheNewContributionMatters(): void
    {
        $contest = $this->compensationContest();
        $this->createSUT()($this->request($contest, originId: 'calc-1', projectContributionId: 'pc-1'));
        $entry = $this->createSUT()($this->request($contest, originId: 'calc-1', projectContributionId: 'pc-2'))->entry;

        (new RemovePendingContestEntryService($this->contestEntryRepository))(new RemovePendingContestEntryRequest('pc-1'));

        $this->assertNotNull($this->contestEntryRepository->find($entry->id()));

        (new ConfirmContestEntryService($this->contestRepository, $this->contestEntryRepository, $this->confirmer(), $this->contestLock, $this->transactionalSession))(new ConfirmContestEntryRequest('pc-2'));

        $this->assertTrue($entry->isConfirmed());
    }

    public function testCalculationContestIgnoresContributionId(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $entry = $this->createSUT()($this->request($contest, projectContributionId: 'pc-1'))->entry;

        $this->assertTrue($entry->isConfirmed());
        $this->assertNull($entry->projectContributionId());
    }

    private function compensationContest(): Contest
    {
        return $this->store(ContestMother::published(accountId: self::ACCOUNT, participationMode: ContestParticipationMode::Compensation));
    }

    private function request(
        Contest $contest,
        string $originId = 'calc-1',
        string $email = 'ana@example.com',
        string $country = 'ES',
        ?string $projectContributionId = null,
        bool $consent = true,
        bool $adult = true,
    ): RegisterContestEntryRequest {
        return new RegisterContestEntryRequest(
            contestId: $contest->id(),
            host: $contest->hostReferences()[0],
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $originId,
            projectContributionId: $projectContributionId,
            name: 'Ana',
            email: $email,
            phone: null,
            country: $country,
            locale: Locale::Spanish,
            consentProof: ContestEntryMother::consentProof($contest),
            consentAccepted: $consent,
            adultConfirmed: $adult,
        );
    }

    private function createSUT(): RegisterContestEntryService
    {
        return new RegisterContestEntryService(
            $this->eligibility(),
            $this->contestEntryRepository,
            $this->confirmer(),
            $this->contestLock,
            $this->transactionalSession,
        );
    }
}
