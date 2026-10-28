<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo\AnswerOrganizationalProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo\AnswerOrganizationalProviderContributionGeneralInfoService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo\GetOrganizationalProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo\GetOrganizationalProviderContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/provider-contribution/{token}/general', name: 'public_organizational_provider_contribution_general', methods: ['GET', 'POST'])]
class GeneralController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OrganizationalProviderContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetOrganizationalProviderContributionGeneralInfoService $getGeneralInfo,
        AnswerOrganizationalProviderContributionGeneralInfoService $answerGeneralInfo,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('provider_contribution_general_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            if (ConsentRequestReader::accepted($request)) {
                $general = $request->request->all('general');

                try {
                    $answerGeneralInfo(new AnswerOrganizationalProviderContributionGeneralInfoRequest(
                        token: $token,
                        companyName: !empty($general['companyName']) ? (string) $general['companyName'] : null,
                        service: !empty($general['service']) ? (string) $general['service'] : null,
                        consentIp: ConsentRequestReader::ip($request),
                        consentUserAgent: ConsentRequestReader::userAgent($request),
                        consentAcceptLanguage: ConsentRequestReader::acceptLanguage($request),
                    ));
                } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
                    return $this->organizationalProviderContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
                } catch (OrganizationalProviderContributionClosedException) {
                    return $this->organizationalProviderContributionClosedResponse();
                }

                return $this->redirectToRoute('public_organizational_provider_contribution_emissions', ['token' => $token]);
            }

            $this->addFlash('danger', new TranslatableMessage('Debes aceptar el tratamiento de tus datos para continuar.'));
        }

        try {
            $response = $getGeneralInfo(new GetOrganizationalProviderContributionGeneralInfoRequest($token));
        } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
            return $this->organizationalProviderContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        } catch (OrganizationalProviderContributionClosedException) {
            return $this->organizationalProviderContributionClosedResponse();
        }

        $this->brandFor($response->contribution->accountId());

        return $this->render('@carbon_footprint/public/provider_contribution/general.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'subjectName' => $response->subjectName,
            'year' => $response->year,
        ]);
    }
}
