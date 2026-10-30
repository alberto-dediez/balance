<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo\AnswerOperatorContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo\AnswerOperatorContributionGeneralInfoService;
use App\CarbonFootprint\Application\OperatorContribution\GetGeneralInfo\GetOperatorContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OperatorContribution\GetGeneralInfo\GetOperatorContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/operator-contribution/{token}/general', name: 'public_operator_contribution_general', methods: ['GET', 'POST'])]
class GeneralController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OperatorContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetOperatorContributionGeneralInfoService $getGeneralInfo,
        AnswerOperatorContributionGeneralInfoService $answerGeneralInfo,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('operator_contribution_general_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            if (ConsentRequestReader::accepted($request)) {
                $general = $request->request->all('general');

                try {
                    $answerGeneralInfo(new AnswerOperatorContributionGeneralInfoRequest(
                        token: $token,
                        contactName: !empty($general['contactName']) ? (string) $general['contactName'] : null,
                        precalculated: !empty($general['precalculated']),
                        consentIp: ConsentRequestReader::ip($request),
                        consentUserAgent: ConsentRequestReader::userAgent($request),
                        consentAcceptLanguage: ConsentRequestReader::acceptLanguage($request),
                    ));
                } catch (OperatorContributionTokenInvalidException|OperatorContributionIgnoredException) {
                    return $this->operatorContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
                } catch (OperatorContributionClosedException) {
                    return $this->operatorContributionClosedResponse();
                } catch (\InvalidArgumentException) {
                    $this->addFlash('danger', new TranslatableMessage('No se han podido guardar los datos generales.'));

                    return $this->redirectToRoute('public_operator_contribution_general', ['token' => $token]);
                }

                return $this->redirectToRoute('public_operator_contribution_data', ['token' => $token]);
            }

            $this->addFlash('danger', new TranslatableMessage('Debes aceptar el tratamiento de tus datos para continuar.'));
        }

        try {
            $response = $getGeneralInfo(new GetOperatorContributionGeneralInfoRequest($token));
        } catch (OperatorContributionTokenInvalidException|OperatorContributionIgnoredException) {
            return $this->operatorContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        } catch (OperatorContributionClosedException) {
            return $this->operatorContributionClosedResponse();
        }

        $this->brandFor($response->contribution->accountId());

        return $this->render('@carbon_footprint/public/operator_contribution/general.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'propertyName' => $response->propertyName,
            'companyName' => $response->companyName,
            'year' => $response->year,
            'hasActivities' => [] !== $response->contribution->activityReferences(),
        ]);
    }
}
