<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContactHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/result/{responseId}/contact',
    name: 'public_carbon_footprint_attendee_survey_contribution_contact',
    methods: ['GET', 'POST'],
)]
class ContactController extends AbstractController
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
        ContributionContactHandler $contactHandler,
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

        $result = $contactHandler->handle(
            new ContributionContext(
                accountId: $accountId,
                co2eqKg: (float) $response->emissions()->co2eq(),
                origin: ProjectContributionOrigin::AttendeeSurveyResponse,
                originId: $responseId,
                originName: $survey->event->name(),
                token: $token,
                allowSharedCredit: true,
            ),
            $this->contributionRoutes($token, $responseId),
            $request,
        );

        if (null !== $result->redirectUrl) {
            return $this->redirect($result->redirectUrl);
        }

        return $this->render('@carbon_footprint/project_contribution/contact.html.twig', $result->parameters + ['event' => $survey->event]);
    }
}
