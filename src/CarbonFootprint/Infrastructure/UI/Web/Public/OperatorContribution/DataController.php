<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\AnswerData\AnswerOperatorContributionDataRequest;
use App\CarbonFootprint\Application\OperatorContribution\AnswerData\AnswerOperatorContributionDataService;
use App\CarbonFootprint\Application\OperatorContribution\GetData\GetOperatorContributionDataRequest;
use App\CarbonFootprint\Application\OperatorContribution\GetData\GetOperatorContributionDataService;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/operator-contribution/{token}/data', name: 'public_operator_contribution_data', methods: ['GET', 'POST'])]
class DataController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OperatorContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        OperatorContributionAuthenticator $authenticator,
        GetOperatorContributionDataService $getData,
        AnswerOperatorContributionDataService $answerData,
        StageAnswerHttpMapper $answerMapper,
        ScopeStepView $stepView,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('operator_contribution_data_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $contribution = $authenticator->authenticate($token)->contribution;
                $data = $request->request->all();

                $answerData(new AnswerOperatorContributionDataRequest(
                    token: $token,
                    scope1Answers: $answerMapper->map($data, $contribution->sectionsFor(Scope::Scope1)),
                    scope2Answers: $answerMapper->map($data, $contribution->sectionsFor(Scope::Scope2)),
                ));
            } catch (OperatorContributionTokenInvalidException|OperatorContributionIgnoredException) {
                return $this->operatorContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
            } catch (OperatorContributionClosedException) {
                return $this->operatorContributionClosedResponse();
            } catch (OperatorContributionConsentRequiredException) {
                return $this->redirectToRoute('public_operator_contribution_general', ['token' => $token]);
            } catch (\InvalidArgumentException|ExchangeRateNotFoundException|IncompatibleUnitsException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                return $this->redirectToRoute('public_operator_contribution_data', ['token' => $token]);
            }

            return $this->redirectToRoute('public_operator_contribution_answered', ['token' => $token]);
        }

        try {
            $response = $getData(new GetOperatorContributionDataRequest($token));
        } catch (OperatorContributionTokenInvalidException|OperatorContributionIgnoredException) {
            return $this->operatorContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        } catch (OperatorContributionClosedException) {
            return $this->operatorContributionClosedResponse();
        } catch (OperatorContributionConsentRequiredException) {
            return $this->redirectToRoute('public_operator_contribution_general', ['token' => $token]);
        }

        $this->brandFor($response->contribution->accountId());

        return $this->render('@carbon_footprint/public/operator_contribution/data.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'scope1' => $stepView->build($response->scope1Sections),
            'scope2' => $stepView->build($response->scope2Sections),
        ]);
    }
}
