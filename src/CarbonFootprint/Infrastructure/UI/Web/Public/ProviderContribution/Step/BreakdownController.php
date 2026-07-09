<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\Step;

use App\CarbonFootprint\Application\ProviderContribution\Answer\AnswerProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Answer\AnswerProviderContributionService;
use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseService;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\PhaseAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View\ProviderContributionPhaseStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\ProviderContributionPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contribution/{token}/breakdown', name: 'public_carbon_footprint_contribution_breakdown')]
class BreakdownController extends AbstractController
{
    use ProviderContributionPublicControllerTrait;

    private const Phase PHASE = Phase::Breakdown;

    public function __construct(private RateLimiterFactory $providerContributionTokenLimiter) {}

    public function __invoke(
        string $token,
        GetProviderContributionPhaseService $getPhase,
        AnswerProviderContributionPhaseService $answerPhase,
        AnswerProviderContributionService $answerProviderContribution,
        PhaseAnswerHttpMapper $answerMapper,
        ProviderContributionPhaseStepView $stepView,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contribution_' . self::PHASE->value . '_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerPhase(new AnswerProviderContributionPhaseRequest(
                    token: $token,
                    phase: self::PHASE,
                    sectionAnswers: $answerMapper->map($request, self::PHASE, self::PHASE->providerSections()),
                ));

                $answerProviderContribution(new AnswerProviderContributionRequest($token));
            } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
                return $this->providerContributionInvalidResponse();
            } catch (ProviderContributionClosedException) {
                return $this->providerContributionClosedResponse();
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('public_carbon_footprint_contribution_breakdown', ['token' => $token]);
            }

            return $this->redirectToRoute('public_carbon_footprint_contribution_answered', ['token' => $token]);
        }

        try {
            $response = $getPhase(new GetProviderContributionPhaseRequest($token, self::PHASE));
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->providerContributionInvalidResponse();
        }

        return $this->render('@carbon_footprint/public/contribution/step/breakdown.html.twig', $stepView->build($response->sections) + [
            'token' => $token,
            'providerContribution' => $response->providerContribution,
        ]);
    }
}
