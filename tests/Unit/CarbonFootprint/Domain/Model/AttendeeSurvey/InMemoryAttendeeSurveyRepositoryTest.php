<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;

class InMemoryAttendeeSurveyRepositoryTest extends AttendeeSurveyRepositoryTest
{
    protected function createSUT(): AttendeeSurveyRepository
    {
        return AttendeeSurveyRepositoryMother::inMemory();
    }
}
