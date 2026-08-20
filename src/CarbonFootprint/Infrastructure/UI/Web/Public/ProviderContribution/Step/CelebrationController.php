<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\Step;

use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseService;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\PhaseAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View\ProviderContributionPhaseStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\ProviderContributionPublicControllerTrait;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contribution/{token}/celebration', name: 'public_carbon_footprint_contribution_celebration')]
class CelebrationController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use ProviderContributionPublicControllerTrait;

    private const Phase PHASE = Phase::Celebration;
    private const string NEXT_ROUTE = 'public_carbon_footprint_contribution_breakdown';

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetProviderContributionPhaseService $getPhase,
        AnswerProviderContributionPhaseService $answerPhase,
        PhaseAnswerHttpMapper $answerMapper,
        ProviderContributionPhaseStepView $stepView,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contribution_' . self::PHASE->value . '_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $worked = 'yes' === $request->request->getString('worked');

            try {
                $answerPhase(new AnswerProviderContributionPhaseRequest(
                    token: $token,
                    phase: self::PHASE,
                    sectionAnswers: $worked
                        ? $answerMapper->map($request, self::PHASE, self::PHASE->providerSections())
                        : array_map(static fn(SectionDefinition $section): SectionAnswer => new SectionAnswer($section, []), self::PHASE->providerSections()),
                ));
            } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
                return $this->providerContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
            } catch (ProviderContributionClosedException) {
                return $this->providerContributionClosedResponse();
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('public_carbon_footprint_contribution_celebration', ['token' => $token]);
            }

            return $this->redirectToRoute(self::NEXT_ROUTE, ['token' => $token]);
        }

        try {
            $response = $getPhase(new GetProviderContributionPhaseRequest($token, self::PHASE));
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->providerContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        }

        $this->brandFor($response->providerContribution->accountId());

        return $this->render('@carbon_footprint/public/contribution/step/celebration.html.twig', $stepView->build($response->sections) + [
            'token' => $token,
            'providerContribution' => $response->providerContribution,
        ]);
    }
}
