<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Update;

use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\Shared\Application\CommandBus\Command;

class UpdateContestRequest implements Command
{
    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    public function __construct(
        public ContestId $contestId,
        public ContestParticipationMode $participationMode,
        public ContestAwardMode $awardMode,
        public ?int $winnersCount,
        public bool $platformDraw,
        public bool $adultsOnly,
        public string $name,
        public string $description,
        public string $prizeDescription,
        public string $terms,
        public string $organizerName,
        public string $organizerTaxId,
        public string $organizerContactEmail,
        public ?string $privacyPolicyUrl,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public array $hosts = [],
        public array $allowedCountries = [],
    ) {}
}
