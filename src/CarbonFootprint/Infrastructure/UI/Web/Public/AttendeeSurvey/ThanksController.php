<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}/thanks', name: 'public_carbon_footprint_attendee_survey_thanks', methods: ['GET'])]
class ThanksController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $attendeeSurveyTokenLimiter,
        private RateLimiterFactory $attendeeSurveyInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, Request $request): Response
    {
        if ($limited = $this->checkAttendeeSurveyRateLimit($this->attendeeSurveyTokenLimiter, $this->attendeeSurveyInvalidTokenLimiter, $request)) {
            return $limited;
        }

        return $this->render('@carbon_footprint/public/attendee_survey/thanks.html.twig', [
            'token' => $token,
        ]);
    }
}
