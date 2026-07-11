<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class AttendeeSurveyIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'attendee_survey_id';
    }

    public function getClassName(): string
    {
        return AttendeeSurveyId::class;
    }
}
