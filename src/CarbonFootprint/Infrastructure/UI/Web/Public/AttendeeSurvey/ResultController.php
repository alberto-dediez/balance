<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Application\Trip\PerPersonTripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}/result/{responseId}', name: 'public_carbon_footprint_attendee_survey_result', methods: ['GET'])]
class ResultController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use BrandedPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $responseId,
        GetAttendeeSurveyByTokenService $getByToken,
        AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        ListContributionOptionsService $listContributionOptions,
        PerPersonTripEmissionsCalculator $perPersonTripEmissionsCalculator,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $this->brandFor($response->attendeeSurvey->accountId());

        $attendeeSurveyResponse = $attendeeSurveyResponseRepository->find(new AttendeeSurveyResponseId($responseId));
        $attendeeSurveyId = $attendeeSurveyResponse?->attendeeSurveyId();

        if (null === $attendeeSurveyId || !$attendeeSurveyId->equals($response->attendeeSurvey->id())) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $mobilityNames = [];

        foreach ($response->mobilities as $mobility) {
            $mobilityNames[(string) $mobility->id()] = $mobility->name();
        }

        $accountId = $response->attendeeSurvey->accountId();
        $hasContributionOptions = false;

        if ($accountId instanceof AccountId) {
            $options = $listContributionOptions(new ListContributionOptionsRequest(
                accountId: $accountId,
                co2eqKg: (float) $attendeeSurveyResponse->emissions()->co2eq(),
            ));

            $hasContributionOptions = !$options->isEmpty();
        }

        return $this->render('@carbon_footprint/public/attendee_survey/result.html.twig', [
            'token' => $token,
            'event' => $response->event,
            'attendeeSurveyResponse' => $attendeeSurveyResponse,
            'emissionsPerPerson' => $perPersonTripEmissionsCalculator->forAttendeeSurveyResponses([$attendeeSurveyResponse]),
            'mobilityNames' => $mobilityNames,
            'hasContributionOptions' => $hasContributionOptions,
        ]);
    }
}
