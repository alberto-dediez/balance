<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution\AttendeeSurveyContributionTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest\ContestPublicHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/result/{responseId}/contest/thanks',
    name: 'public_carbon_footprint_attendee_survey_contest_thanks',
    methods: ['GET'],
)]
class ThanksController extends AbstractController
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
        ContestPublicHandler $contestHandler,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        $survey = $this->resolveSurvey($token, $getByToken);

        if (null === $survey) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $response = $this->resolveResponse($responseId, $survey, $attendeeSurveyResponseRepository);
        $accountId = $this->accountIdOf($survey);

        if (null === $response || !$accountId instanceof AccountId) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $routes = $this->contributionRoutes($token, $responseId);
        $contest = $contestHandler->activeContest(ContestPublicHandler::hostForEvent($survey->event->id()));
        $entry = null === $contest ? null : $contestHandler->entryFor($contest, new ContributionContext(
            accountId: $accountId,
            co2eqKg: (float) $response->emissions()->co2eq(),
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $responseId,
            originName: $survey->event->name(),
            token: $token,
        ));

        if (null === $contest || null === $entry) {
            return $this->redirectToRoute($routes->result, $routes->resultRouteParams);
        }

        return $this->render('@carbon_footprint/public/contest/thanks.html.twig', [
            'token' => $token,
            'event' => $survey->event,
            'routes' => $routes,
            'contest' => $contest,
            'contestEntry' => $entry,
        ]);
    }
}
