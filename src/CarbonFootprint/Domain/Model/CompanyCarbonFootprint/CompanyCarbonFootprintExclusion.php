<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class CompanyCarbonFootprintExclusion implements SourceExclusion
{
    public function __construct(
        private CompanyCarbonFootprintExclusionId $id,
        private CompanyCarbonFootprint $companyCarbonFootprint,
        private Scope $scope,
        private Source $source,
        private ExclusionReason $reason,
        private ?string $comment = null,
        private \DateTimeImmutable $excludedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): CompanyCarbonFootprintExclusionId
    {
        return $this->id;
    }

    public function companyCarbonFootprint(): CompanyCarbonFootprint
    {
        return $this->companyCarbonFootprint;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function stage(): CalculationStage
    {
        return $this->scope;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function reason(): ExclusionReason
    {
        return $this->reason;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function excludedAt(): \DateTimeImmutable
    {
        return $this->excludedAt;
    }
}
