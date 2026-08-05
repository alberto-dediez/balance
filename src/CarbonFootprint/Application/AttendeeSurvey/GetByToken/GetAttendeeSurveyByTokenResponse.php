<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetByToken;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\EventOperations\Domain\Model\Event\Event;

final class GetAttendeeSurveyByTokenResponse
{
    /** @param array<Mobility> $mobilities */
    public function __construct(
        public readonly AttendeeSurvey $attendeeSurvey,
        public readonly Event $event,
        public readonly array $mobilities,
        public readonly bool $geoEnabled = false,
        public readonly int $maxLegs = 4,
    ) {}
}
