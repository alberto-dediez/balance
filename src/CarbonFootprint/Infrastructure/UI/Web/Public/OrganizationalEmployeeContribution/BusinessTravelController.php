<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel\AnswerOrganizationalEmployeeContributionBusinessTravelRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel\AnswerOrganizationalEmployeeContributionBusinessTravelService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenService;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\TripAnswer;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/organizational-employee-contribution/{token}/business-travel', name: 'public_organizational_employee_contribution_business_travel', methods: ['GET', 'POST'])]
class BusinessTravelController extends AbstractController
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
        AnswerOrganizationalEmployeeContributionBusinessTravelService $answerBusinessTravel,
        Request $request,
    ): Response {
        if ($limited = $this->checkOrganizationalEmployeeContributionRateLimit($this->employeeContributionTokenLimiter, $this->employeeContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetOrganizationalEmployeeContributionByTokenRequest($token));
        } catch (OrganizationalEmployeeContributionTokenInvalidException|OrganizationalEmployeeContributionIgnoredException) {
            return $this->organizationalEmployeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
        }

        if (!$response->contribution->stepStatus()->isCommutingAnswered()) {
            return $this->redirectToRoute('public_organizational_employee_contribution_commuting', ['token' => $token]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employee_contribution_business_travel_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerBusinessTravel(new AnswerOrganizationalEmployeeContributionBusinessTravelRequest(
                    token: $token,
                    trips: $request->request->getBoolean('noTrips') ? [] : $this->trips($request),
                ));
            } catch (OrganizationalEmployeeContributionTokenInvalidException|OrganizationalEmployeeContributionIgnoredException) {
                return $this->organizationalEmployeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
            } catch (OrganizationalEmployeeContributionClosedException) {
                return $this->organizationalEmployeeContributionClosedResponse();
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar los datos: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                return $this->redirectToRoute('public_organizational_employee_contribution_business_travel', ['token' => $token]);
            }

            return $this->redirectToRoute('public_organizational_employee_contribution_answered', ['token' => $token]);
        }

        $this->brandFor($response->accountId);

        return $this->render('@carbon_footprint/public/organizational_employee_contribution/business_travel.html.twig', [
            'token' => $token,
            'contribution' => $response->contribution,
            'subjectName' => $response->subjectName,
            'year' => $response->year,
            'mobilities' => $response->mobilities,
        ]);
    }

    /** @return list<TripAnswer> */
    private function trips(Request $request): array
    {
        $trips = [];

        foreach ($request->request->all('trips') as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mobilityId = (string) ($row['mobilityId'] ?? '');
            $distanceKm = (float) ($row['distanceKm'] ?? 0);

            if ('' === $mobilityId || $distanceKm <= 0) {
                continue;
            }

            $trips[] = new TripAnswer(
                mobilityId: new MobilityId($mobilityId),
                distanceKm: $distanceKm,
                trips: (int) ($row['trips'] ?? 1),
                vehicleOccupants: (int) ($row['vehicleOccupants'] ?? 1),
                hotelNights: (int) ($row['hotelNights'] ?? 0),
            );
        }

        return $trips;
    }
}
