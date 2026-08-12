<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution\ContributionCheckoutHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/result/{responseId}/compensate/{projectId}',
    name: 'public_carbon_footprint_attendee_survey_contribution_checkout',
    methods: ['GET', 'POST'],
)]
class CheckoutController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $responseId,
        string $projectId,
        GetAttendeeSurveyByTokenService $getByToken,
        AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        ContributionCheckoutHandler $checkoutHandler,
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

        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('attendee_survey_contribution_' . $token, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $result = $checkoutHandler->handle(
            new ContributionContext(
                accountId: $accountId,
                co2eqKg: (float) $response->emissions()->co2eq(),
                origin: ProjectContributionOrigin::AttendeeSurveyResponse,
                originId: $responseId,
                originName: $survey->event->name(),
                token: $token,
            ),
            $this->contributionRoutes($token, $responseId),
            $projectId,
            $request,
        );

        if ($result->invalid) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        if (null !== $result->redirectUrl) {
            return $this->redirect($result->redirectUrl);
        }

        $parameters = $result->parameters + ['event' => $survey->event];

        if ($request->isXmlHttpRequest()) {
            return $this->render('@carbon_footprint/public/project_contribution/_summary.html.twig', $parameters);
        }

        return $this->render('@carbon_footprint/public/project_contribution/checkout.html.twig', $parameters);
    }
}
