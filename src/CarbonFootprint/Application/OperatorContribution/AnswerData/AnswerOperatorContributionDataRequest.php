<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\AnswerData;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;

final class AnswerOperatorContributionDataRequest
{
    /**
     * @param list<SectionAnswer> $scope1Answers
     * @param list<SectionAnswer> $scope2Answers
     */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly array $scope1Answers,
        public readonly array $scope2Answers,
    ) {}
}
