<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;

class AttendeeSurveyRepositoryMother
{
    public static function inMemory(): InMemoryAttendeeSurveyRepository
    {
        return new InMemoryAttendeeSurveyRepository();
    }
}
