<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class ContestEntryMother
{
    public static function pending(
        Contest $contest,
        ?string $email = null,
        ?string $originId = null,
        ?string $projectContributionId = 'contribution-1',
        ?ContestHostReference $host = null,
        string $name = 'Ana García',
        string $country = 'ES',
        ProjectContributionOrigin $origin = ProjectContributionOrigin::AttendeeSurveyResponse,
    ): ContestEntry {
        $accountId = $contest->accountId();
        assert(null !== $accountId);

        $entry = new ContestEntry(ContestEntryId::generate());
        $entry->register(
            contestId: $contest->id(),
            accountId: $accountId,
            host: $host ?? $contest->hostReferences()[0] ?? ContestMother::eventHost(),
            origin: $origin,
            originId: $originId ?? 'origin-' . bin2hex(random_bytes(4)),
            projectContributionId: $projectContributionId,
            name: $name,
            email: $email ?? 'user-' . bin2hex(random_bytes(4)) . '@example.com',
            phone: '600000000',
            country: $country,
            locale: Locale::Spanish,
            consentProof: self::consentProof($contest),
            at: new \DateTimeImmutable('NOW'),
        );

        return $entry;
    }

    public static function confirmed(Contest $contest, int $entryNumber, bool $winner = false, ?string $email = null, ?string $originId = null): ContestEntry
    {
        $entry = self::pending($contest, email: $email, originId: $originId, projectContributionId: null);
        $entry->confirm($entryNumber, new \DateTimeImmutable('NOW'));

        if ($winner) {
            $entry->award(new \DateTimeImmutable('NOW'));
        }

        return $entry;
    }

    public static function consentProof(Contest $contest): ConsentProof
    {
        return new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $contest->termsVersion(),
            locale: Locale::Spanish->value,
            ip: '127.0.0.1',
            userAgent: 'PHPUnit',
            acceptLanguage: 'es',
        );
    }
}
