<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Retry\RetryCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Retry\RetryCarbonFootprintReportService;
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

#[Route('/carbon-footprint-reports/{reportId}/retry', name: 'carbon_footprint_reports_retry', methods: ['GET'])]
#[CsrfProtected('carbon_footprint_report')]
class RetryController extends AbstractController
{
    use GetCarbonFootprintReportFromIdTrait;

    public function __invoke(
        string $reportId,
        CarbonFootprintReportRepository $reportRepository,
        CarbonFootprintReportResultsRoute $resultsRoute,
        RetryCarbonFootprintReportService $retryReport,
        LoggedUser $loggedUser,
    ): Response {
        $report = $this->getCarbonFootprintReportFromId($reportId, $reportRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $report);

        try {
            $retryReport(new RetryCarbonFootprintReportRequest($report->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se está volviendo a generar el informe.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (CarbonFootprintReportTransitionNotAllowedException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido volver a generar el informe: {error}', ['{error}' => $e->getMessage()]));
        }

        $results = $resultsRoute->resolve($report);

        return $this->redirectToRoute($results['route'], $results['params']);
    }
}
