<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting\AnswerOrganizationalEmployeeContributionCommutingRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting\AnswerOrganizationalEmployeeContributionCommutingService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/organizational-employee-contribution/{token}/commuting', name: 'public_organizational_employee_contribution_commuting', methods: ['GET', 'POST'])]
class CommutingController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OrganizationalEmployeeContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $employeeContributionTokenLimiter,
        private RateLimiterFactory $employeeContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetOrganizationalEmployeeContributionByTokenService $getByToken,
        AnswerOrganizationalEmployeeContributionCommutingService $answerCommuting,
        Request $request,
    ): Response {
        if ($limited = $this->checkOrganizationalEmployeeContributionRateLimit($this->employeeContributionTokenLimiter, $this->employeeContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employee_contribution_commuting_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            if (ConsentRequestReader::accepted($request)) {
                $noCommuting = $request->request->getBoolean('noCommuting');
                $workplacePropertyId = $request->request->getString('workplacePropertyId');
                $weeksPerYear = $request->request->getString('weeksPerYear');

                try {
                    $answerCommuting(new AnswerOrganizationalEmployeeContributionCommutingRequest(
                        token: $token,
                        workplacePropertyId: '' !== $workplacePropertyId ? new PropertyId($workplacePropertyId) : null,
                        weeksPerYear: !$noCommuting && '' !== $weeksPerYear ? (int) $weeksPerYear : null,
                        commutes: $noCommuting ? [] : $this->commutes($request),
                        consentGranted: true,
                        consentIp: ConsentRequestReader::ip($request),
                        consentUserAgent: ConsentRequestReader::userAgent($request),
                        consentAcceptLanguage: ConsentRequestReader::acceptLanguage($request),
                    ));
                } catch (OrganizationalEmployeeContributionTokenInvalidException|OrganizationalEmployeeContributionIgnoredException) {
                    return $this->organizationalEmployeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
                } catch (OrganizationalEmployeeContributionClosedException) {
                    return $this->organizationalEmployeeContributionClosedResponse();
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('danger', new TranslatableMessage('No se han podido guardar los datos: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                    return $this->redirectToRoute('public_organizational_employee_contribution_commuting', ['token' => $token]);
                }

                return $this->redirectToRoute('public_organizational_employee_contribution_business_travel', ['token' => $token]);
            }

            $this->addFlash('danger', new TranslatableMessage('Debes aceptar el tratamiento de tus datos para continuar.'));
        }

        try {
            $response = $getByToken(new GetOrganizationalEmployeeContributionByTokenRequest($token));
        } catch (OrganizationalEmployeeContributionTokenInvalidException|OrganizationalEmployeeContributionIgnoredException) {
            return $this->organizationalEmployeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
        }

        $this->brandFor($response->accountId);

        return $this->render('@carbon_footprint/public/organizational_employee_contribution/commuting.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'subjectName' => $response->subjectName,
            'year' => $response->year,
            'mobilities' => $response->mobilities,
            'workplaces' => $response->workplaces,
            'isCompany' => CarbonFootprintReportSubjectType::Company === $response->contribution->subjectType(),
        ]);
    }

    /** @return list<CommuteAnswer> */
    private function commutes(Request $request): array
    {
        $commutes = [];

        foreach ($request->request->all('commutes') as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mobilityId = (string) ($row['mobilityId'] ?? '');
            $distanceKm = (float) ($row['distanceKm'] ?? 0);

            if ('' === $mobilityId || $distanceKm <= 0) {
                continue;
            }

            $commutes[] = new CommuteAnswer(
                mobilityId: new MobilityId($mobilityId),
                distanceKm: $distanceKm,
                daysPerWeek: (int) ($row['daysPerWeek'] ?? 0),
                vehicleOccupants: (int) ($row['vehicleOccupants'] ?? 1),
            );
        }

        return $commutes;
    }
}
