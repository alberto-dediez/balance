<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenResponse;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionGalleryTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionRoutes;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;

trait AttendeeSurveyContributionTrait
{
    use BrandedPublicControllerTrait;
    use ContributionGalleryTrait;

    private function resolveSurvey(
        string $token,
        GetAttendeeSurveyByTokenService $getByToken,
    ): ?GetAttendeeSurveyByTokenResponse {
        try {
            $survey = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            return null;
        }

        $this->brandFor($this->accountIdOf($survey));

        return $survey;
    }

    private function resolveResponse(
        string $responseId,
        GetAttendeeSurveyByTokenResponse $survey,
        AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
    ): ?AttendeeSurveyResponse {
        $response = $attendeeSurveyResponseRepository->find(new AttendeeSurveyResponseId($responseId));
        $attendeeSurveyId = $response?->attendeeSurveyId();

        if (null === $attendeeSurveyId || !$attendeeSurveyId->equals($survey->attendeeSurvey->id())) {
            return null;
        }

        return $response;
    }

    private function accountIdOf(GetAttendeeSurveyByTokenResponse $survey): ?AccountId
    {
        return $survey->attendeeSurvey->accountId();
    }

    private function contributionRoutes(string $token, string $responseId): ContributionRoutes
    {
        return new ContributionRoutes(
            result: 'public_carbon_footprint_attendee_survey_result',
            choose: 'public_carbon_footprint_attendee_survey_contribution_choose',
            project: 'public_carbon_footprint_attendee_survey_contribution_project',
            checkout: 'public_carbon_footprint_attendee_survey_contribution_checkout',
            return: 'public_carbon_footprint_attendee_survey_contribution_return',
            cancel: 'public_carbon_footprint_attendee_survey_contribution_cancel',
            certificate: 'public_carbon_footprint_attendee_survey_contribution_certificate',
            certificateEmail: 'public_carbon_footprint_attendee_survey_contribution_certificate_email',
            routeParams: ['token' => $token, 'responseId' => $responseId],
            contributionRouteParams: ['token' => $token],
            resultRouteParams: ['token' => $token, 'responseId' => $responseId],
            layout: '@carbon_footprint/public/attendee_survey/layout.html.twig',
            csrfTokenId: 'attendee_survey_contribution_' . $token,
        );
    }
}
