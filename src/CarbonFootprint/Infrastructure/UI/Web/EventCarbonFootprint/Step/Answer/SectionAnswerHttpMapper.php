<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('carbon_footprint.section_answer_http_mapper')]
interface SectionAnswerHttpMapper
{
    public function activityType(): ActivityType;

    /** @param array<array-key, mixed> $row */
    public function map(array $row): ?ActivityAnswer;
}
