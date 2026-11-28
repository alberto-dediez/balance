<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestAllowedCountry
{
    public function __construct(
        private ContestAllowedCountryId $id,
        private Contest $contest,
        private string $countryIso,
    ) {}

    public function id(): ContestAllowedCountryId
    {
        return $this->id;
    }

    public function contest(): Contest
    {
        return $this->contest;
    }

    public function countryIso(): string
    {
        return $this->countryIso;
    }
}
