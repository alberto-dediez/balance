<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Report;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue\ReissueEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue\ReissueEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/report/reissue', name: 'events_carbon_footprint_report_reissue', methods: ['POST'])]
class ReissueReportController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        EventCarbonFootprintReportRepository $reportRepository,
        ReissueEventCarbonFootprintReportService $reissueReport,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$this->isCsrfTokenValid('reissue_report_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $report = $reportRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id())
            ?? throw $this->createNotFoundException('Informe no encontrado');

        try {
            $reissueReport(new ReissueEventCarbonFootprintReportRequest($report->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reemitir el informe: {error}', ['{error}' => $e->getMessage()]));

            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId]);
        }

        $this->addFlash('success', new TranslatableMessage('Se está reemitiendo el informe, te notificaremos cuando esté disponible.'));

        return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId]);
    }
}
