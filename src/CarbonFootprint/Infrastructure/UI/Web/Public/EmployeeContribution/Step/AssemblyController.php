<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\EmployeeContribution\Step;

use App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase\AnswerEmployeeContributionPhaseRequest;
use App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase\AnswerEmployeeContributionPhaseService;
use App\CarbonFootprint\Application\EmployeeContribution\GetByToken\GetEmployeeContributionByTokenRequest;
use App\CarbonFootprint\Application\EmployeeContribution\GetByToken\GetEmployeeContributionByTokenService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\EmployeeContribution\EmployeeContributionPublicControllerTrait;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/employee-contribution/{token}/assembly', name: 'public_carbon_footprint_employee_contribution_assembly')]
class AssemblyController extends AbstractController
{
    use EmployeeContributionPublicControllerTrait;
    use BrandedPublicControllerTrait;

    private const Phase PHASE = Phase::Assembly;
    private const string NEXT_ROUTE = 'public_carbon_footprint_employee_contribution_celebration';

    public function __construct(
        private RateLimiterFactory $employeeContributionTokenLimiter,
        private RateLimiterFactory $employeeContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetEmployeeContributionByTokenService $getByToken,
        AnswerEmployeeContributionPhaseService $answerPhase,
        Request $request,
    ): Response {
        if ($limited = $this->checkEmployeeContributionRateLimit($this->employeeContributionTokenLimiter, $this->employeeContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employee_contribution_' . self::PHASE->value . '_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $worked = $request->request->getBoolean('worked');
            $mobilityId = $request->request->getString('mobilityId');
            $distanceKm = $request->request->getString('distanceKm');
            $vehicleOccupants = $request->request->getString('vehicleOccupants');
            $days = $request->request->getString('days');
            $hotelNights = $request->request->getString('hotelNights');

            try {
                $answerPhase(new AnswerEmployeeContributionPhaseRequest(
                    token: $token,
                    phase: self::PHASE,
                    worked: $worked,
                    mobilityId: $worked && '' !== $mobilityId ? new MobilityId($mobilityId) : null,
                    distanceKm: $worked && '' !== $distanceKm ? (float) $distanceKm : null,
                    vehicleOccupants: $worked && '' !== $vehicleOccupants ? (int) $vehicleOccupants : null,
                    days: $worked && '' !== $days ? (int) $days : null,
                    hotelNights: $worked && '' !== $hotelNights ? (int) $hotelNights : null,
                ));
            } catch (EmployeeContributionTokenInvalidException|EmployeeContributionIgnoredException) {
                return $this->employeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
            } catch (EmployeeContributionClosedException) {
                return $this->employeeContributionClosedResponse();
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar los datos: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('public_carbon_footprint_employee_contribution_assembly', ['token' => $token]);
            }

            return $this->redirectToRoute(self::NEXT_ROUTE, ['token' => $token]);
        }

        try {
            $response = $getByToken(new GetEmployeeContributionByTokenRequest($token));
        } catch (EmployeeContributionTokenInvalidException|EmployeeContributionIgnoredException) {
            return $this->employeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
        }

        $this->brandFor($response->employeeContribution->accountId());

        return $this->render('@carbon_footprint/public/employee_contribution/step/assembly.html.twig', [
            'token' => $token,
            'employeeContribution' => $response->employeeContribution,
            'event' => $response->event,
            'mobilities' => $response->mobilities,
        ]);
    }
}
