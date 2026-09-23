<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Reissue\ReissueCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reissue\ReissueCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint-reports/{reportId}/reissue', name: 'carbon_footprint_reports_reissue', methods: ['GET'])]
#[CsrfProtected('carbon_footprint_report')]
class ReissueController extends AbstractController
{
    use GetCarbonFootprintReportFromIdTrait;

    public function __invoke(
        string $reportId,
        CarbonFootprintReportRepository $reportRepository,
        CarbonFootprintReportResultsRoute $resultsRoute,
        ReissueCarbonFootprintReportService $reissueReport,
        LoggedUser $loggedUser,
    ): Response {
        $report = $this->getCarbonFootprintReportFromId($reportId, $reportRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $report);

        try {
            $reissueReport(new ReissueCarbonFootprintReportRequest($report->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se está reemitiendo el informe, te notificaremos cuando esté disponible.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (CarbonFootprintReportTransitionNotAllowedException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reemitir el informe: {error}', ['{error}' => $e->getMessage()]));
        }

        $results = $resultsRoute->resolve($report);

        return $this->redirectToRoute($results['route'], $results['params']);
    }
}
