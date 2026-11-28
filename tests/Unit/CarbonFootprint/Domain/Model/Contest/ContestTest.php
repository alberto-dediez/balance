<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAlreadyResolvedException;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestClosed;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotEditableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotPublishableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotRemovableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublished;
use App\CarbonFootprint\Domain\Model\Contest\ContestRegistered;
use App\CarbonFootprint\Domain\Model\Contest\ContestResolved;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ContestTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testRegisterPublishesEventAndLeavesDraft(): void
    {
        $contest = ContestMother::draft(
            allowedCountries: ['ES', 'PT'],
            hosts: [ContestMother::eventHost(), ContestMother::propertyHost()],
        );

        $this->assertInstanceOf(ContestRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Draft, $contest->status());
        $this->assertSame(['ES', 'PT'], $contest->allowedCountryIsos());
        $this->assertCount(2, $contest->hostReferences());
        $this->assertTrue($contest->hasHost(ContestMother::propertyHost()));
        $this->assertFalse($contest->hasHost(ContestMother::tripLinkHost()));
    }

    public function testRegisterRequiresWinnersCountForDrawAndFirstEntrants(): void
    {
        foreach ([ContestAwardMode::Draw, ContestAwardMode::FirstEntrants] as $awardMode) {
            try {
                $this->register(awardMode: $awardMode, winnersCount: null);
                $this->fail('Expected exception for ' . $awardMode->value);
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testRegisterIgnoresWinnersCountForEveryone(): void
    {
        $contest = $this->register(awardMode: ContestAwardMode::Everyone, winnersCount: 5);

        $this->assertNull($contest->winnersCount());
    }

    public function testRegisterRejectsPlatformDrawOutsideDraw(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(awardMode: ContestAwardMode::FirstEntrants, winnersCount: 2, platformDraw: true);
    }

    public function testRegisterRejectsInvertedDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(startsAt: new \DateTimeImmutable('+2 days'), endsAt: new \DateTimeImmutable('+1 day'));
    }

    public function testRegisterRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(name: '  ');
    }

    public function testRegisterRejectsRepeatedHosts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(hosts: [ContestMother::eventHost(), ContestMother::eventHost()]);
    }

    public function testRegisterRejectsRepeatedCountries(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(allowedCountries: ['ES', 'ES']);
    }

    public function testRegisterRejectsInvalidCountryIso(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(allowedCountries: ['es']);
    }

    public function testPublishRecordsResponsibility(): void
    {
        $contest = ContestMother::draft();
        $userId = UserId::generate();
        $at = new \DateTimeImmutable('2026-11-27 10:00:00');

        $contest->publish($userId, $at);

        $this->assertInstanceOf(ContestPublished::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Published, $contest->status());
        $this->assertSame($userId, $contest->responsibilityAcceptedBy());
        $this->assertEquals($at, $contest->publishedAt());
        $this->assertSame((string) $contest->id() . ':20261127100000', $contest->termsVersion());
    }

    public function testPublishWithoutHostsFails(): void
    {
        $contest = $this->register(hosts: []);

        $this->expectException(ContestNotPublishableException::class);

        $contest->publish(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testPublishWithoutTermsFails(): void
    {
        $contest = $this->register(terms: '');

        $this->expectException(ContestNotPublishableException::class);

        $contest->publish(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testPublishWithoutOrganizerFails(): void
    {
        $contest = $this->register(organizerTaxId: '');

        $this->expectException(ContestNotPublishableException::class);

        $contest->publish(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testDraftIsFullyEditable(): void
    {
        $contest = ContestMother::draft();

        $this->update($contest, awardMode: ContestAwardMode::FirstEntrants, winnersCount: 3, allowedCountries: ['FR']);

        $this->assertSame(ContestAwardMode::FirstEntrants, $contest->awardMode());
        $this->assertSame(3, $contest->winnersCount());
        $this->assertSame(['FR'], $contest->allowedCountryIsos());
    }

    public function testPublishedAllowsAddingAndRemovingHosts(): void
    {
        $contest = ContestMother::published();

        $this->update($contest, hosts: [ContestMother::eventHost(), ContestMother::propertyHost()]);
        $this->assertCount(2, $contest->hostReferences());

        $this->update($contest, hosts: [ContestMother::propertyHost()]);
        $this->assertSame([ContestMother::propertyHost()->key()], array_map(static fn(ContestHostReference $host): string => $host->key(), $contest->hostReferences()));
    }

    public function testPublishedCannotRemoveTheLastHost(): void
    {
        $contest = ContestMother::published();

        $this->expectException(ContestNotEditableException::class);

        $this->update($contest, hosts: []);
    }

    public function testPublishedAllowsTextsOrganizerAndFutureEnd(): void
    {
        $contest = ContestMother::published();
        $endsAt = new \DateTimeImmutable('+60 days');

        $this->update($contest, name: 'Nuevo nombre', organizerName: 'Otro organizador', endsAt: $endsAt);

        $this->assertSame('Nuevo nombre', $contest->name());
        $this->assertSame('Otro organizador', $contest->organizerName());
        $this->assertEquals($endsAt, $contest->endsAt());
    }

    public function testPublishedRejectsPastEnd(): void
    {
        $contest = ContestMother::published(startsAt: new \DateTimeImmutable('-10 days'));

        $this->expectException(ContestNotEditableException::class);

        $this->update($contest, endsAt: new \DateTimeImmutable('-1 hour'));
    }

    public function testPublishedRejectsMechanicChanges(): void
    {
        $changes = [
            'participation' => fn(Contest $contest) => $this->update($contest, participationMode: ContestParticipationMode::Compensation),
            'award' => fn(Contest $contest) => $this->update($contest, awardMode: ContestAwardMode::FirstEntrants),
            'winners' => fn(Contest $contest) => $this->update($contest, winnersCount: 2),
            'adults' => fn(Contest $contest) => $this->update($contest, adultsOnly: true),
            'countries' => fn(Contest $contest) => $this->update($contest, allowedCountries: ['ES']),
            'startsAt' => fn(Contest $contest) => $this->update($contest, startsAt: new \DateTimeImmutable('-2 days')),
        ];

        foreach ($changes as $label => $change) {
            $contest = ContestMother::published();

            try {
                $change($contest);
                $this->fail('Expected exception changing ' . $label);
            } catch (ContestNotEditableException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testClosedIsNotEditable(): void
    {
        $contest = ContestMother::closed();

        $this->expectException(ContestNotEditableException::class);

        $this->update($contest, name: 'Otro');
    }

    public function testUpdateInAnotherLocaleKeepsBaseTexts(): void
    {
        $contest = ContestMother::draft(name: 'Concurso');

        $this->update($contest, name: 'Contest', locale: Locale::English);

        $this->assertSame('Concurso', $contest->name(Locale::Spanish));
        $this->assertSame('Contest', $contest->name(Locale::English));
    }

    public function testAcceptsEntriesWithinTheBoundaries(): void
    {
        $startsAt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $endsAt = new \DateTimeImmutable('2026-01-31 23:59:59');
        $contest = ContestMother::published(startsAt: $startsAt, endsAt: $endsAt);

        $this->assertTrue($contest->acceptsEntries($startsAt));
        $this->assertTrue($contest->acceptsEntries($endsAt));
        $this->assertFalse($contest->acceptsEntries($startsAt->modify('-1 second')));
        $this->assertFalse($contest->acceptsEntries($endsAt->modify('+1 second')));
    }

    public function testDraftDoesNotAcceptEntries(): void
    {
        $this->assertFalse(ContestMother::draft()->acceptsEntries(new \DateTimeImmutable('NOW')));
    }

    public function testCloseResolvesWithoutPlatformDraw(): void
    {
        $contest = ContestMother::published();

        $contest->close(new \DateTimeImmutable('NOW'));

        $this->assertInstanceOf(ContestResolved::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Resolved, $contest->status());
        $this->assertNull($contest->drawnBy());
    }

    public function testCloseKeepsClosedWithPlatformDraw(): void
    {
        $contest = ContestMother::publishedPlatformDraw(2);

        $contest->close(new \DateTimeImmutable('NOW'));

        $this->assertInstanceOf(ContestClosed::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Closed, $contest->status());
    }

    public function testResolveByDrawTwiceFails(): void
    {
        $contest = ContestMother::publishedPlatformDraw(2);
        $contest->close(new \DateTimeImmutable('NOW'));
        $contest->resolveByDraw(UserId::generate(), 5, new \DateTimeImmutable('NOW'));

        $this->assertSame(ContestStatus::Resolved, $contest->status());
        $this->assertSame(5, $contest->drawCandidates());

        $this->expectException(ContestAlreadyResolvedException::class);

        $contest->resolveByDraw(UserId::generate(), 5, new \DateTimeImmutable('NOW'));
    }

    public function testResolveByDrawRequiresClosed(): void
    {
        $contest = ContestMother::publishedPlatformDraw(2);

        $this->expectException(ContestNotEditableException::class);

        $contest->resolveByDraw(UserId::generate(), 5, new \DateTimeImmutable('NOW'));
    }

    public function testAllowsCountry(): void
    {
        $this->assertTrue(ContestMother::draft()->allowsCountry('FR'));

        $restricted = ContestMother::draft(allowedCountries: ['ES', 'PT']);

        $this->assertTrue($restricted->allowsCountry('ES'));
        $this->assertTrue($restricted->allowsCountry('pt'));
        $this->assertFalse($restricted->allowsCountry('FR'));
    }

    public function testAwardRulesByMode(): void
    {
        $firstEntrants = ContestMother::publishedFirstEntrants(2);
        $this->assertTrue($firstEntrants->awardsOnConfirmation(1));
        $this->assertFalse($firstEntrants->awardsOnConfirmation(2));
        $this->assertFalse($firstEntrants->hasAwardedEveryPrize(1));
        $this->assertTrue($firstEntrants->hasAwardedEveryPrize(2));
        $this->assertTrue($firstEntrants->reassignsPrizes());

        $everyone = ContestMother::publishedEveryone();
        $this->assertTrue($everyone->awardsOnConfirmation(100));
        $this->assertFalse($everyone->hasAwardedEveryPrize(100));
        $this->assertFalse($everyone->reassignsPrizes());

        $draw = ContestMother::published(winnersCount: 1);
        $this->assertFalse($draw->awardsOnConfirmation(0));
        $this->assertFalse($draw->reassignsPrizes());

        $platformDraw = ContestMother::publishedPlatformDraw(1);
        $this->assertFalse($platformDraw->reassignsPrizes());
        $platformDraw->close(new \DateTimeImmutable('NOW'));
        $this->assertFalse($platformDraw->reassignsPrizes());
        $platformDraw->resolveByDraw(UserId::generate(), 3, new \DateTimeImmutable('NOW'));
        $this->assertTrue($platformDraw->reassignsPrizes());
    }

    public function testCountConfirmedEntryIsCorrelative(): void
    {
        $contest = ContestMother::published();

        $this->assertSame(1, $contest->countConfirmedEntry());
        $this->assertSame(2, $contest->countConfirmedEntry());
        $this->assertSame(2, $contest->confirmedEntries());
    }

    public function testRecordPrizeRedrawn(): void
    {
        $contest = ContestMother::publishedPlatformDraw(1);
        $contest->close(new \DateTimeImmutable('NOW'));
        $contest->resolveByDraw(UserId::generate(), 3, new \DateTimeImmutable('NOW'));

        $contest->recordPrizeRedrawn(new \DateTimeImmutable('NOW'));

        $this->assertSame(1, $contest->redrawnPrizes());
    }

    public function testRecordPrizeRedrawnRequiresPlatformDrawResolved(): void
    {
        $this->expectException(ContestNotEditableException::class);

        ContestMother::published()->recordPrizeRedrawn(new \DateTimeImmutable('NOW'));
    }

    public function testPurgeOnlyWhenClosed(): void
    {
        $contest = ContestMother::closed();
        $userId = UserId::generate();

        $contest->purgePersonalData($userId, new \DateTimeImmutable('NOW'));

        $this->assertTrue($contest->isPersonalDataPurged());
        $this->assertSame($userId, $contest->personalDataPurgedBy());

        $this->expectException(ContestNotEditableException::class);

        ContestMother::published()->purgePersonalData($userId, new \DateTimeImmutable('NOW'));
    }

    public function testPurgeRejectsAlreadyPurgedContest(): void
    {
        $contest = ContestMother::closed();
        $contest->purgePersonalData(UserId::generate(), new \DateTimeImmutable('NOW'));

        $this->expectException(ContestNotEditableException::class);

        $contest->purgePersonalData(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testRemoveOnlyDraft(): void
    {
        ContestMother::draft()->remove();

        $this->expectException(ContestNotRemovableException::class);

        ContestMother::published()->remove();
    }

    /**
     * @param list<ContestHostReference>|null $hosts
     * @param list<string>                    $allowedCountries
     */
    private function register(
        ContestAwardMode $awardMode = ContestAwardMode::Draw,
        ?int $winnersCount = 1,
        bool $platformDraw = false,
        string $name = 'Concurso',
        string $terms = 'Bases',
        string $organizerTaxId = 'B12345678',
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null,
        ?array $hosts = null,
        array $allowedCountries = [],
    ): Contest {
        $contest = new Contest(ContestId::generate());
        $contest->register(
            accountId: AccountId::generate(),
            participationMode: ContestParticipationMode::Calculation,
            awardMode: $awardMode,
            winnersCount: $winnersCount,
            platformDraw: $platformDraw,
            adultsOnly: true,
            name: $name,
            description: 'Descripción',
            prizeDescription: 'Premio',
            terms: $terms,
            organizerName: 'Organizador',
            organizerTaxId: $organizerTaxId,
            organizerContactEmail: 'org@example.com',
            privacyPolicyUrl: null,
            startsAt: $startsAt ?? new \DateTimeImmutable('-1 day'),
            endsAt: $endsAt ?? new \DateTimeImmutable('+1 day'),
            hosts: $hosts ?? [ContestMother::eventHost()],
            allowedCountries: $allowedCountries,
        );

        return $contest;
    }

    /**
     * @param list<ContestHostReference>|null $hosts
     * @param list<string>|null               $allowedCountries
     */
    private function update(
        Contest $contest,
        ?ContestParticipationMode $participationMode = null,
        ?ContestAwardMode $awardMode = null,
        ?int $winnersCount = null,
        ?bool $adultsOnly = null,
        ?string $name = null,
        ?string $organizerName = null,
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null,
        ?array $hosts = null,
        ?array $allowedCountries = null,
        ?Locale $locale = null,
    ): void {
        $contest->update(
            participationMode: $participationMode ?? $contest->participationMode(),
            awardMode: $awardMode ?? $contest->awardMode(),
            winnersCount: $winnersCount ?? $contest->winnersCount(),
            platformDraw: $contest->platformDraw(),
            adultsOnly: $adultsOnly ?? $contest->adultsOnly(),
            name: $name ?? $contest->name(),
            description: $contest->description(),
            prizeDescription: $contest->prizeDescription(),
            terms: $contest->terms(),
            organizerName: $organizerName ?? $contest->organizerName(),
            organizerTaxId: $contest->organizerTaxId(),
            organizerContactEmail: $contest->organizerContactEmail(),
            privacyPolicyUrl: $contest->privacyPolicyUrl(),
            startsAt: $startsAt ?? $contest->startsAt(),
            endsAt: $endsAt ?? $contest->endsAt(),
            hosts: $hosts ?? $contest->hostReferences(),
            allowedCountries: $allowedCountries ?? $contest->allowedCountryIsos(),
            locale: $locale,
        );
    }
}
