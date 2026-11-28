<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Application\Trip\PerPersonTripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution\AttendeeSurveyContributionTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest\ContestPublicHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}/result/{responseId}', name: 'public_carbon_footprint_attendee_survey_result', methods: ['GET'])]
class ResultController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;

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
        ContestPublicHandler $contestHandler,
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
        $offersContact = false;

        if ($accountId instanceof AccountId) {
            $options = $listContributionOptions(new ListContributionOptionsRequest(
                accountId: $accountId,
                co2eqKg: (float) $attendeeSurveyResponse->emissions()->co2eq(),
                allowSharedCredit: true,
            ));

            $hasContributionOptions = !$options->isEmpty();
            $offersContact = $options->offersContact();
        }

        $contest = $contestHandler->activeContest(ContestPublicHandler::hostForEvent($response->event->id()));
        $contestEntry = null !== $contest && $accountId instanceof AccountId ? $contestHandler->entryFor($contest, new ContributionContext(
            accountId: $accountId,
            co2eqKg: (float) $attendeeSurveyResponse->emissions()->co2eq(),
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $responseId,
            originName: $response->event->name(),
            token: $token,
        )) : null;

        return $this->render('@carbon_footprint/public/attendee_survey/result.html.twig', [
            'token' => $token,
            'event' => $response->event,
            'attendeeSurveyResponse' => $attendeeSurveyResponse,
            'emissionsPerPerson' => $perPersonTripEmissionsCalculator->forAttendeeSurveyResponses([$attendeeSurveyResponse]),
            'mobilityNames' => $mobilityNames,
            'hasContributionOptions' => $hasContributionOptions,
            'offersContact' => $offersContact,
        ] + $contestHandler->viewParameters($contest, $contestEntry, $this->contributionRoutes($token, $responseId)));
    }
}
