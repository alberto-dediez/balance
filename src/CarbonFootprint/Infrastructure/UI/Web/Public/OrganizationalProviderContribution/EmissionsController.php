<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions\AnswerOrganizationalProviderContributionEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions\AnswerOrganizationalProviderContributionEmissionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions\GetOrganizationalProviderContributionEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions\GetOrganizationalProviderContributionEmissionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/provider-contribution/{token}/emissions', name: 'public_organizational_provider_contribution_emissions', methods: ['GET', 'POST'])]
class EmissionsController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OrganizationalProviderContributionPublicControllerTrait;

    private const string MODE_REPORTED = 'reported';
    private const string MODE_ACTIVITIES = 'activities';

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        OrganizationalProviderContributionAuthenticator $authenticator,
        GetOrganizationalProviderContributionEmissionsService $getEmissions,
        AnswerOrganizationalProviderContributionEmissionsService $answerEmissions,
        StageAnswerHttpMapper $answerMapper,
        ScopeStepView $stepView,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('provider_contribution_emissions_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $authentication = $authenticator->authenticate($token);
                $modes = $request->request->all('mode');

                if (!self::hasModeForEverySource($authentication->contribution->sources(), $modes)) {
                    $this->addFlash('danger', new TranslatableMessage('Elige cómo quieres informar las emisiones de cada categoría.'));

                    return $this->redirectToRoute('public_organizational_provider_contribution_emissions', ['token' => $token]);
                }

                $answerEmissions(new AnswerOrganizationalProviderContributionEmissionsRequest(
                    token: $token,
                    sectionAnswers: array_map(
                        static fn(SectionAnswer $answer): SectionAnswer => self::isChosen($answer, $modes) ? $answer : new SectionAnswer($answer->definition, []),
                        $answerMapper->map($request->request->all(), Scope::Scope3->providerSectionsFor($authentication->contribution->sources())),
                    ),
                ));
            } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
                return $this->organizationalProviderContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
            } catch (OrganizationalProviderContributionClosedException) {
                return $this->organizationalProviderContributionClosedResponse();
            } catch (OrganizationalProviderContributionConsentRequiredException) {
                return $this->redirectToRoute('public_organizational_provider_contribution_general', ['token' => $token]);
            } catch (\InvalidArgumentException|ExchangeRateNotFoundException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('public_organizational_provider_contribution_emissions', ['token' => $token]);
            }

            return $this->redirectToRoute('public_organizational_provider_contribution_answered', ['token' => $token]);
        }

        try {
            $response = $getEmissions(new GetOrganizationalProviderContributionEmissionsRequest($token));
        } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
            return $this->organizationalProviderContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        } catch (OrganizationalProviderContributionClosedException) {
            return $this->organizationalProviderContributionClosedResponse();
        }

        if (!$response->contribution->hasConsent()) {
            return $this->redirectToRoute('public_organizational_provider_contribution_general', ['token' => $token]);
        }

        $this->brandFor($response->contribution->accountId());

        $views = [];
        $modes = [];
        foreach ($response->sectionsBySource as $source => $sections) {
            $views[$source] = $stepView->build($sections);
            $modes[$source] = $this->currentMode($sections);
        }

        return $this->render('@carbon_footprint/public/provider_contribution/emissions.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'sectionsBySource' => $views,
            'modes' => $modes,
        ]);
    }

    /**
     * @param list<Source>            $sources
     * @param array<array-key, mixed> $modes
     */
    private static function hasModeForEverySource(array $sources, array $modes): bool
    {
        foreach ($sources as $source) {
            if (!in_array($modes[$source->value] ?? null, [self::MODE_REPORTED, self::MODE_ACTIVITIES], true)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<array-key, mixed> $modes */
    private static function isChosen(SectionAnswer $answer, array $modes): bool
    {
        $mode = $modes[$answer->definition->source->value] ?? null;

        return ActivityType::ReportedEmissions === $answer->definition->activityType
            ? self::MODE_REPORTED === $mode
            : self::MODE_ACTIVITIES === $mode;
    }

    /** @param list<StageSection> $sections */
    private function currentMode(array $sections): ?string
    {
        foreach ($sections as $section) {
            if ([] !== $section->activities) {
                return ActivityType::ReportedEmissions === $section->definition->activityType ? self::MODE_REPORTED : self::MODE_ACTIVITIES;
            }
        }

        return null;
    }
}
