<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

interface DestinationRepository
{
    public function add(Destination $destination): void;

    public function remove(Destination $destination): void;

    public function find(DestinationId $destinationId): ?Destination;

    public function findByNameAndCountry(string $name, CountryId $countryId): ?Destination;

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Destination;

    public function findByFilter(DestinationFilter $filter): array;

    public function totalResultsByFilter(DestinationFilter $filter): int;
}
