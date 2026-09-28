<?php

declare(strict_types=1);

namespace App\Materiality\Application\Seed;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Shared\Application\CommandBus\CommandResponse;

class SeedStandardMaterialityMastersResponse implements CommandResponse
{
    /**
     * @param list<MaterialityTopic>    $registeredTopics
     * @param list<MaterialityQuestion> $registeredQuestions
     */
    public function __construct(
        public array $registeredTopics = [],
        public int $skippedTopics = 0,
        public array $registeredQuestions = [],
        public bool $questionsSkipped = false,
    ) {}
}
