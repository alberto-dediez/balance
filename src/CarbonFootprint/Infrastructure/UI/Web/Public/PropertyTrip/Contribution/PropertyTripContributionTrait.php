<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\Trip\TripCalculationContributionOrigin;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenResponse;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionGalleryTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionRoutes;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;

trait PropertyTripContributionTrait
{
    use BrandedPublicControllerTrait;
    use ContributionGalleryTrait;

    private function contributionRoutes(TripCalculationLink $tripCalculationLink, string $token, string $tripCalculationId): ContributionRoutes
    {
        $routes = PublicTripRoutes::for($tripCalculationLink);

        return new ContributionRoutes(
            result: $routes->name('_result'),
            choose: $routes->name('_contribution_choose'),
            project: $routes->name('_contribution_project'),
            checkout: $routes->name('_contribution_checkout'),
            return: $routes->name('_contribution_return'),
            cancel: $routes->name('_contribution_cancel'),
            certificate: $routes->name('_contribution_certificate'),
            certificateEmail: $routes->name('_contribution_certificate_email'),
            routeParams: ['token' => $token, 'tripCalculationId' => $tripCalculationId],
            contributionRouteParams: ['token' => $token],
            resultRouteParams: ['token' => $token, 'tripCalculationId' => $tripCalculationId],
            layout: '@carbon_footprint/public/property_trip/layout.html.twig',
            csrfTokenId: 'property_trip_' . $token,
        );
    }

    private function resolveLink(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
    ): ?GetTripCalculationLinkByTokenResponse {
        try {
            $link = $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return null;
        }

        $this->brandFor($link->tripCalculationLink->accountId());

        return $link;
    }

    private function resolveTripCalculation(
        string $tripCalculationId,
        GetTripCalculationLinkByTokenResponse $link,
        TripCalculationRepository $tripCalculationRepository,
    ): ?TripCalculation {
        $tripCalculation = $tripCalculationRepository->find(new TripCalculationId($tripCalculationId));
        $linkId = $tripCalculation?->tripCalculationLinkId();

        if (!$linkId instanceof TripCalculationLinkId || !$linkId->equals($link->tripCalculationLink->id())) {
            return null;
        }

        return $tripCalculation;
    }

    private function resolveContribution(
        string $contributionId,
        GetTripCalculationLinkByTokenResponse $link,
        ProjectContributionRepository $projectContributionRepository,
        TripCalculationRepository $tripCalculationRepository,
    ): ?ProjectContribution {
        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($link);
        $contributionAccountId = $projectContribution?->accountId();
        $originId = $projectContribution?->originId();

        if (
            null === $projectContribution
            || null === $accountId
            || null === $contributionAccountId
            || !$accountId->equals($contributionAccountId)
            || TripCalculationContributionOrigin::for($link->tripCalculationLink) !== $projectContribution->origin()
            || null === $originId
            || null === $this->resolveTripCalculation($originId, $link, $tripCalculationRepository)
        ) {
            return null;
        }

        return $projectContribution;
    }

    private function accountIdOf(GetTripCalculationLinkByTokenResponse $link): ?AccountId
    {
        return $link->tripCalculationLink->accountId() ?? $link->property?->accountId();
    }
}
