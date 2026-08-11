<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenResponse;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution\ContributionRoutes;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution\PublicGalleryTrait;
use App\Security\Domain\Model\Account\AccountId;

trait PropertyTripContributionTrait
{
    use PublicGalleryTrait;

    private function contributionRoutes(string $token, string $tripCalculationId): ContributionRoutes
    {
        return new ContributionRoutes(
            result: 'public_carbon_footprint_property_trip_result',
            choose: 'public_carbon_footprint_property_trip_contribution_choose',
            project: 'public_carbon_footprint_property_trip_contribution_project',
            checkout: 'public_carbon_footprint_property_trip_contribution_checkout',
            return: 'public_carbon_footprint_property_trip_contribution_return',
            cancel: 'public_carbon_footprint_property_trip_contribution_cancel',
            certificate: 'public_carbon_footprint_property_trip_contribution_certificate',
            certificateEmail: 'public_carbon_footprint_property_trip_contribution_certificate_email',
            routeParams: ['token' => $token, 'tripCalculationId' => $tripCalculationId],
            layout: '@carbon_footprint/public/property_trip/layout.html.twig',
            csrfTokenId: 'property_trip_' . $token,
        );
    }

    private function resolveLink(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
    ): ?GetTripCalculationLinkByTokenResponse {
        try {
            return $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return null;
        }
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

    private function accountIdOf(GetTripCalculationLinkByTokenResponse $link): ?AccountId
    {
        return $link->tripCalculationLink->accountId() ?? $link->property->accountId();
    }
}
