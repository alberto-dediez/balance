<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;

class InMemoryAttendeeSurveyResponseRepositoryTest extends AttendeeSurveyResponseRepositoryTest
{
    protected function createSUT(): AttendeeSurveyResponseRepository
    {
        return AttendeeSurveyResponseRepositoryMother::inMemory();
    }
}
