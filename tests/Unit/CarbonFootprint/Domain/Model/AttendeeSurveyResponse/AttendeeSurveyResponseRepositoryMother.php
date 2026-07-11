<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;

class AttendeeSurveyResponseRepositoryMother
{
    public static function inMemory(): InMemoryAttendeeSurveyResponseRepository
    {
        return new InMemoryAttendeeSurveyResponseRepository();
    }
}
