<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final class EventCarbonFootprintReportCompensationLine
{
    /**
     * @param list<string>                                                      $masterSerials
     * @param list<array{serial: string, startTonnes: float, endTonnes: float}> $creditLots
     */
    public function __construct(
        public readonly EventCarbonFootprintReportCompensationScope $scope,
        public readonly ?string $beneficiaryName,
        public readonly ?string $certificateCode,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly string $projectName,
        public readonly ?string $projectRegistryId,
        public readonly float $tonnes,
        public readonly array $masterSerials = [],
        public readonly array $creditLots = [],
        public readonly ?string $verificationUrl = null,
    ) {}
}
