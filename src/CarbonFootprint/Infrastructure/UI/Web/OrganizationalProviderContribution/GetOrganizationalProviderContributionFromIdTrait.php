<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;

trait GetOrganizationalProviderContributionFromIdTrait
{
    private function getOrganizationalProviderContributionFromId(
        string $contributionId,
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        OrganizationalProviderContributionRepository $repository,
    ): OrganizationalProviderContribution {
        $contribution = $repository->find(new OrganizationalProviderContributionId($contributionId));

        if (null === $contribution
            || $contribution->isRemoved()
            || $subjectType !== $contribution->subjectType()
            || $carbonFootprintId !== $contribution->carbonFootprintId()
        ) {
            throw $this->createNotFoundException('Aportación de proveedor no encontrada');
        }

        return $contribution;
    }

    private function organizationalProviderContributionName(OrganizationalProviderContribution $contribution, ProviderRepository $providerRepository): string
    {
        $providerId = $contribution->providerId();
        $providerName = $providerId instanceof ProviderId ? $providerRepository->find($providerId)?->name() : null;

        return null !== $providerName && '' !== $providerName ? $providerName : ($contribution->companyName() ?? '');
    }
}
