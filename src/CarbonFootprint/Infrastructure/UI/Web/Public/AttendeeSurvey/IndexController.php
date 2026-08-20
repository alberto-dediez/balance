<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicLanding\PublicLandingRenderTrait;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}', name: 'public_carbon_footprint_attendee_survey', methods: ['GET'])]
class IndexController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use BrandedPublicControllerTrait;
    use PublicLandingRenderTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetAttendeeSurveyByTokenService $getByToken,
        GetActivePublicLandingService $getActivePublicLanding,
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

        if ($response->attendeeSurvey->status()->isClosed()) {
            return $this->attendeeSurveyClosedResponse();
        }

        $landing = $this->renderPublicLanding(
            getActivePublicLanding: $getActivePublicLanding,
            ownerType: PublicLandingOwnerType::Event,
            ownerId: (string) $response->event->id(),
            ownerName: $response->event->name(),
            ctaRoute: 'public_carbon_footprint_attendee_survey_intercity',
            ctaRouteParams: ['token' => $token],
        );

        return $landing ?? $this->redirectToRoute('public_carbon_footprint_attendee_survey_intercity', ['token' => $token]);
    }
}
