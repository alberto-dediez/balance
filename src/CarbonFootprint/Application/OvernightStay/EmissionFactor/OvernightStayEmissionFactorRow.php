<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;

class OvernightStayEmissionFactorRow
{
    public function __construct(
        public ?string $overnightStayId = null,
        public ?string $overnightStayName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}

    public static function fromOvernightStay(OvernightStayId $overnightStayId, string $overnightStayName, ?EmissionFactor $emissionFactor): self
    {
        return new self(
            overnightStayId: (string) $overnightStayId,
            overnightStayName: $overnightStayName,
            co2eq: $emissionFactor?->co2eq(),
            co2: $emissionFactor?->co2(),
            ch4: $emissionFactor?->ch4(),
            n2o: $emissionFactor?->n2o(),
            source: $emissionFactor?->source(),
        );
    }

    public function isEmpty(): bool
    {
        return '' === (string) $this->co2eq
            && '' === (string) $this->co2
            && '' === (string) $this->ch4
            && '' === (string) $this->n2o;
    }

    public function overnightStayId(): OvernightStayId
    {
        return new OvernightStayId((string) $this->overnightStayId);
    }

    public function toEmissionFactor(int $year): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: (string) $this->co2eq,
            co2: (string) $this->co2,
            ch4: (string) $this->ch4,
            n2o: (string) $this->n2o,
            year: $year,
            unit: Unit::Pernoctacion,
            source: $this->source,
        );
    }
}
