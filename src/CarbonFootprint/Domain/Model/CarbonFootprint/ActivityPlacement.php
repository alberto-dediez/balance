<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;

final class ActivityPlacement
{
    public function __construct(
        public readonly CalculationStage $stage,
        public readonly Source $source,
        public readonly ?ProviderId $providerId = null,
        public readonly bool $fromEmployees = false,
        public readonly ?PropertyId $propertyId = null,
    ) {}

    public function equals(self $other): bool
    {
        if ($this->stage->key() !== $other->stage->key()) {
            return false;
        }

        if ($this->source !== $other->source) {
            return false;
        }

        if ($this->fromEmployees !== $other->fromEmployees) {
            return false;
        }

        if (null === $this->providerId xor null === $other->providerId) {
            return false;
        }

        if (null !== $this->providerId && null !== $other->providerId && !$this->providerId->equals($other->providerId)) {
            return false;
        }

        if (null === $this->propertyId xor null === $other->propertyId) {
            return false;
        }

        return null === $this->propertyId || null === $other->propertyId || $this->propertyId->equals($other->propertyId);
    }

    public function withSource(Source $source): self
    {
        return new self(
            stage: $this->stage,
            source: $source,
            providerId: $this->providerId,
            fromEmployees: $this->fromEmployees,
            propertyId: $this->propertyId,
        );
    }
}
