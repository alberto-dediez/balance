<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}', name: 'public_carbon_footprint_attendee_survey', methods: ['GET'])]
class IndexController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;

    public function __construct(private RateLimiterFactory $attendeeSurveyTokenLimiter) {}

    public function __invoke(string $token, GetAttendeeSurveyByTokenService $getByToken, Request $request): Response
    {
        if ($limited = $this->checkAttendeeSurveyRateLimit($this->attendeeSurveyTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            return $this->attendeeSurveyInvalidResponse();
        }

        if ($response->attendeeSurvey->status()->isClosed()) {
            return $this->attendeeSurveyClosedResponse();
        }

        return $this->redirectToRoute('public_carbon_footprint_attendee_survey_intercity', ['token' => $token]);
    }
}
