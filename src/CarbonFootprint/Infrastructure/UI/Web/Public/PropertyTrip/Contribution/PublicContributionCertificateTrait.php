<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\Security\Domain\Model\Account\AccountId;

trait PublicContributionCertificateTrait
{
    /**
     * Resolves the certificate only when the link token is valid and the contribution behind it
     * belongs to the same account: the same chain of checks the thank-you page makes.
     */
    private function resolveCertificate(
        string $token,
        string $contributionId,
        GetTripCalculationLinkByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
    ): ?ContributionCertificate {
        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return null;
        }

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($link);
        $contributionAccountId = $projectContribution?->accountId();

        if (
            null === $projectContribution
            || !$accountId instanceof AccountId
            || !$contributionAccountId instanceof AccountId
            || !$accountId->equals($contributionAccountId)
        ) {
            return null;
        }

        return $contributionCertificateRepository->findByProjectContributionId($projectContribution->id());
    }
}
