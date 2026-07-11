<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;

class AttendeeSurveyMother
{
    public static function registeredAttendeeSurvey(): AttendeeSurvey
    {
        return self::fromEventId((string) EventId::generate());
    }

    public static function fromEventId(string $eventId): AttendeeSurvey
    {
        return self::from(
            eventId: $eventId,
            id: (string) AttendeeSurveyId::generate(),
        );
    }

    public static function fromEventIdAndAccount(string $eventId, string $accountId): AttendeeSurvey
    {
        return self::from(
            eventId: $eventId,
            id: (string) AttendeeSurveyId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $eventId,
        string $id,
        ?string $accountId = null,
    ): AttendeeSurvey {
        $attendeeSurvey = new AttendeeSurvey(id: new AttendeeSurveyId($id));
        $attendeeSurvey->register(
            eventId: new EventId($eventId),
            accountId: new AccountId($accountId ?? 'account-id'),
            token: AttendeeSurvey::generateToken(),
        );

        return $attendeeSurvey;
    }

    public static function random(): AttendeeSurvey
    {
        return self::registeredAttendeeSurvey();
    }
}
