<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Update\UpdateContestRequest;
use App\CarbonFootprint\Application\Contest\Update\UpdateContestService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class UpdateContestServiceTest extends ContestApplicationTestCase
{
    public function testUpdatesTheContest(): void
    {
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host]));

        $response = $this->createSUT()($this->request($contest, name: 'Renombrado', hosts: [$host, $this->propertyHost()]), $this->owner());

        $this->assertSame('Renombrado', $response->contest->name());
        $this->assertCount(2, $response->contest->hostReferences());
    }

    public function testTranslatesInANonDefaultLocale(): void
    {
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host], name: 'Concurso'));
        $this->locale(Locale::English);

        $this->createSUT()($this->request($contest, name: 'Contest'), $this->owner());

        $this->assertSame('Concurso', $contest->name(Locale::Spanish));
        $this->assertSame('Contest', $contest->name(Locale::English));
    }

    public function testAddingABusyCalculatorToAPublishedContestFails(): void
    {
        $busy = $this->propertyHost();
        $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$busy]));
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));

        $this->expectException(ContestHostAlreadyInUseException::class);
        $this->expectExceptionMessage('Hotel');

        $this->createSUT()($this->request($contest, hosts: [$host, $busy]), $this->owner());
    }

    public function testAddingACalculatorToAPublishedContestTakesThePublicationLock(): void
    {
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));

        $this->createSUT()($this->request($contest, hosts: [$host, $this->propertyHost()]), $this->owner());

        $this->assertSame([self::ACCOUNT], $this->publicationLock->acquired());
        $this->assertCount(2, $contest->hostReferences());
    }

    public function testUpdatingADraftDoesNotTakeThePublicationLock(): void
    {
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host]));

        $this->createSUT()($this->request($contest, hosts: [$host, $this->propertyHost()]), $this->owner());

        $this->assertSame([], $this->publicationLock->acquired());
    }

    public function testCalculatorPublishedConcurrentlyWhileWaitingForTheLockFails(): void
    {
        $busy = $this->propertyHost();
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));
        $lock = new FakeContestPublicationLock(function () use ($busy): void {
            $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$busy]));
        });

        $this->expectException(ContestHostAlreadyInUseException::class);
        $this->expectExceptionMessage('Hotel');

        $this->createSUT($lock)($this->request($contest, hosts: [$host, $busy]), $this->owner());
    }

    public function testRemovingACalculatorFromAPublishedContestKeepsTheRest(): void
    {
        $first = $this->eventHost();
        $second = $this->propertyHost();
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$first, $second]));

        $this->createSUT()($this->request($contest, hosts: [$second]), $this->owner());

        $this->assertFalse($contest->hasHost($first));
        $this->assertTrue($contest->hasHost($second));
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::OTHER_ACCOUNT, hosts: [$this->eventHost(self::OTHER_ACCOUNT)]));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request($contest), $this->owner());
    }

    public function testNewCalculatorOfAnotherAccountIsDenied(): void
    {
        $host = $this->eventHost();
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host]));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request($contest, hosts: [$host, $this->eventHost(self::OTHER_ACCOUNT)]), $this->owner());
    }

    /** @param list<ContestHostReference>|null $hosts */
    private function request(Contest $contest, ?string $name = null, ?array $hosts = null): UpdateContestRequest
    {
        return new UpdateContestRequest(
            contestId: $contest->id(),
            participationMode: $contest->participationMode(),
            awardMode: $contest->awardMode(),
            winnersCount: $contest->winnersCount(),
            platformDraw: $contest->platformDraw(),
            adultsOnly: $contest->adultsOnly(),
            name: $name ?? $contest->name(),
            description: $contest->description(),
            prizeDescription: $contest->prizeDescription(),
            terms: $contest->terms(),
            organizerName: $contest->organizerName(),
            organizerTaxId: $contest->organizerTaxId(),
            organizerContactEmail: $contest->organizerContactEmail(),
            privacyPolicyUrl: $contest->privacyPolicyUrl(),
            startsAt: $contest->startsAt(),
            endsAt: $contest->endsAt(),
            hosts: $hosts ?? $contest->hostReferences(),
            allowedCountries: $contest->allowedCountryIsos(),
        );
    }

    private function createSUT(?ContestPublicationLock $lock = null): UpdateContestService
    {
        return new UpdateContestService(
            $this->contestRepository,
            $this->authorizer(),
            $this->hostLocator(),
            $lock ?? $this->publicationLock,
            $this->transactionalSession,
        );
    }
}
