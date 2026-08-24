<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class AttendeeSurveyFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?EventId $eventId = null,
        public ?AttendeeSurveyStatus $status = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
