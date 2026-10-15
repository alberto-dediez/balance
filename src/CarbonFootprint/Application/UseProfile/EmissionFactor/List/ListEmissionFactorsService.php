<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor\List;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private UseProfileRepository $repository,
        private UseProfileAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: UseProfileId::generate()), SecurityAction::List, $user);

        $useProfiles = $this->repository->findByFilter(new UseProfileFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($useProfiles, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            useProfiles: $useProfiles,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<UseProfile> $useProfiles */
    private function latestYear(array $useProfiles, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($useProfiles as $useProfile) {
            foreach ($useProfile->emissionFactors() as $useProfileEmissionFactor) {
                if (!$useProfileEmissionFactor->emissionFactorGroupId()->equals($emissionFactorGroupId)) {
                    continue;
                }

                $year = $useProfileEmissionFactor->year();

                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
