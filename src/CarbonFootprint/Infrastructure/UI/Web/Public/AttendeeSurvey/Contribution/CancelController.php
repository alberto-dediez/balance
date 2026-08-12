<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/contribution/{contributionId}/cancel',
    name: 'public_carbon_footprint_attendee_survey_contribution_cancel',
    methods: ['GET'],
)]
class CancelController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetAttendeeSurveyByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        $survey = $this->resolveSurvey($token, $getByToken);

        if (null === $survey) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($survey);
        $contributionAccountId = $projectContribution?->accountId();
        $projectId = $projectContribution?->projectId();
        $originId = $projectContribution?->originId();

        if (
            null === $projectContribution
            || null === $accountId
            || null === $contributionAccountId
            || !$accountId->equals($contributionAccountId)
            || null === $projectId
            || null === $originId
            || ProjectContributionOrigin::AttendeeSurveyResponse !== $projectContribution->origin()
        ) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $this->addFlash('warning', new TranslatableMessage('Has cancelado el pago. Puedes volver a intentarlo cuando quieras.'));

        return $this->redirectToRoute('public_carbon_footprint_attendee_survey_contribution_checkout', [
            'token' => $token,
            'responseId' => $originId,
            'projectId' => (string) $projectId,
        ]);
    }
}
