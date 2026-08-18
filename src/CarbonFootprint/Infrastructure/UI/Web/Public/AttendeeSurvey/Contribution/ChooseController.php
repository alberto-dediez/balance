<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/result/{responseId}/compensate',
    name: 'public_carbon_footprint_attendee_survey_contribution_choose',
    methods: ['GET'],
)]
class ChooseController extends AbstractController
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
        MediaRepository $mediaRepository,
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

        $options = $listContributionOptions(new ListContributionOptionsRequest(
            accountId: $accountId,
            co2eqKg: (float) $response->emissions()->co2eq(),
        ));

        $covers = [];

        foreach ($options->options as $option) {
            $gallery = $this->publicGallery($option->project->gallery(), $mediaRepository);
            $covers[(string) $option->project->id()] = $gallery[0] ?? null;
        }

        return $this->render('@carbon_footprint/project_contribution/choose.html.twig', [
            'token' => $token,
            'event' => $survey->event,
            'co2eqKg' => (float) $response->emissions()->co2eq(),
            'routes' => $this->contributionRoutes($token, $responseId),
            'options' => $options->options,
            'covers' => $covers,
        ]);
    }
}
