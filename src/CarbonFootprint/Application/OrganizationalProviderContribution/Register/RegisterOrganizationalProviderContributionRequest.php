<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Register;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class RegisterOrganizationalProviderContributionRequest implements Command
{
    /** @param list<Source> $sources */
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
        public ProviderId $providerId,
        public ?PropertyId $propertyId,
        public ProviderContributionMode $mode,
        public array $sources,
    ) {}
}
