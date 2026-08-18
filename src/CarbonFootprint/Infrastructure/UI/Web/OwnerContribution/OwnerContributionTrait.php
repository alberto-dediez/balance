<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionGalleryTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionRoutes;
use App\Security\Domain\Model\User\User;

trait OwnerContributionTrait
{
    use ContributionGalleryTrait;

    private function resolveContext(
        string $origin,
        string $originId,
        OwnerContributionContextResolver $contextResolver,
        User $user,
    ): OwnerContributionContext {
        $context = $contextResolver($origin, $originId, $user);

        if (null === $context) {
            throw $this->createAccessDeniedException('No puedes compensar emisiones desde aquí.');
        }

        return $context;
    }

    private function contributionRoutes(OwnerContributionContext $context): ContributionRoutes
    {
        $routeParams = ['origin' => $context->origin->value, 'originId' => $context->originId];

        return new ContributionRoutes(
            result: $this->resultRoute($context->origin),
            choose: 'owner_contribution_choose',
            project: 'owner_contribution_project',
            checkout: 'owner_contribution_checkout',
            return: 'owner_contribution_confirmation',
            cancel: 'owner_contribution_cancel',
            certificate: 'owner_contribution_certificate',
            certificateEmail: 'owner_contribution_certificate_email',
            routeParams: $routeParams,
            contributionRouteParams: $routeParams,
            resultRouteParams: $this->resultRouteParams($context),
            layout: '@carbon_footprint/owner_contribution/layout.html.twig',
            csrfTokenId: 'owner_contribution_' . $context->originId,
        );
    }

    private function resultRoute(ProjectContributionOrigin $origin): string
    {
        return match ($origin) {
            ProjectContributionOrigin::EventCarbonFootprint => 'events_carbon_footprint_results',
            ProjectContributionOrigin::Property => 'carbon_footprint_property_trip_calculations_responses',
            default => 'dashboard',
        };
    }

    /** @return array<string, string> */
    private function resultRouteParams(OwnerContributionContext $context): array
    {
        return match ($context->origin) {
            ProjectContributionOrigin::EventCarbonFootprint => ['eventId' => $context->originId],
            ProjectContributionOrigin::Property => ['propertyId' => $context->originId],
            default => [],
        };
    }
}
