<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class ContestMother
{
    /**
     * @param list<ContestHostReference>|null $hosts
     * @param list<string>                    $allowedCountries
     */
    public static function draft(
        ?string $accountId = null,
        ContestParticipationMode $participationMode = ContestParticipationMode::Calculation,
        ContestAwardMode $awardMode = ContestAwardMode::Draw,
        ?int $winnersCount = 1,
        bool $platformDraw = false,
        bool $adultsOnly = false,
        string $name = 'Concurso de verano',
        ?array $hosts = null,
        array $allowedCountries = [],
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null,
        ?string $id = null,
    ): Contest {
        $contest = new Contest(id: null === $id ? ContestId::generate() : new ContestId($id));
        $contest->register(
            accountId: null === $accountId ? AccountId::generate() : new AccountId($accountId),
            participationMode: $participationMode,
            awardMode: $awardMode,
            winnersCount: $awardMode->needsWinnersCount() ? $winnersCount : null,
            platformDraw: $platformDraw,
            adultsOnly: $adultsOnly,
            name: $name,
            description: 'Descripción del concurso',
            prizeDescription: 'Una noche de hotel',
            terms: 'Bases del concurso',
            organizerName: 'Organizador S.L.',
            organizerTaxId: 'B12345678',
            organizerContactEmail: 'organizador@example.com',
            privacyPolicyUrl: null,
            startsAt: $startsAt ?? new \DateTimeImmutable('-1 day'),
            endsAt: $endsAt ?? new \DateTimeImmutable('+30 days'),
            hosts: $hosts ?? [self::eventHost()],
            allowedCountries: $allowedCountries,
        );

        return $contest;
    }

    /**
     * @param list<ContestHostReference>|null $hosts
     * @param list<string>                    $allowedCountries
     */
    public static function published(
        ?string $accountId = null,
        ContestParticipationMode $participationMode = ContestParticipationMode::Calculation,
        ContestAwardMode $awardMode = ContestAwardMode::Draw,
        ?int $winnersCount = 1,
        bool $platformDraw = false,
        bool $adultsOnly = false,
        string $name = 'Concurso de verano',
        ?array $hosts = null,
        array $allowedCountries = [],
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null,
        ?string $id = null,
    ): Contest {
        $contest = self::draft($accountId, $participationMode, $awardMode, $winnersCount, $platformDraw, $adultsOnly, $name, $hosts, $allowedCountries, $startsAt, $endsAt, $id);
        $contest->publish(UserId::generate(), new \DateTimeImmutable('NOW'));

        return $contest;
    }

    /**
     * @param list<ContestHostReference>|null $hosts
     * @param list<string>                    $allowedCountries
     */
    public static function closed(
        ?string $accountId = null,
        ContestParticipationMode $participationMode = ContestParticipationMode::Calculation,
        ContestAwardMode $awardMode = ContestAwardMode::Draw,
        ?int $winnersCount = 1,
        bool $platformDraw = false,
        bool $adultsOnly = false,
        string $name = 'Concurso de verano',
        ?array $hosts = null,
        array $allowedCountries = [],
        ?string $id = null,
    ): Contest {
        $contest = self::published($accountId, $participationMode, $awardMode, $winnersCount, $platformDraw, $adultsOnly, $name, $hosts, $allowedCountries, null, null, $id);
        $contest->close(new \DateTimeImmutable('NOW'));

        return $contest;
    }

    public static function publishedFirstEntrants(int $winnersCount, ?string $accountId = null, ContestParticipationMode $participationMode = ContestParticipationMode::Calculation): Contest
    {
        return self::published(accountId: $accountId, participationMode: $participationMode, awardMode: ContestAwardMode::FirstEntrants, winnersCount: $winnersCount);
    }

    public static function publishedEveryone(?string $accountId = null, ContestParticipationMode $participationMode = ContestParticipationMode::Calculation): Contest
    {
        return self::published(accountId: $accountId, participationMode: $participationMode, awardMode: ContestAwardMode::Everyone, winnersCount: null);
    }

    public static function publishedPlatformDraw(int $winnersCount, ?string $accountId = null): Contest
    {
        return self::published(accountId: $accountId, awardMode: ContestAwardMode::Draw, winnersCount: $winnersCount, platformDraw: true);
    }

    public static function eventHost(string $eventId = 'event-1'): ContestHostReference
    {
        return new ContestHostReference(ContestHostType::Event, $eventId);
    }

    public static function propertyHost(string $propertyId = 'property-1'): ContestHostReference
    {
        return new ContestHostReference(ContestHostType::Property, $propertyId);
    }

    public static function tripLinkHost(string $linkId = 'trip-link-1'): ContestHostReference
    {
        return new ContestHostReference(ContestHostType::TripCalculationLink, $linkId);
    }
}
