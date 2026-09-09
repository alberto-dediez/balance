<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Report;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Download\DownloadEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Download\DownloadEventCarbonFootprintReportResponse;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Download\DownloadEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/report/download', name: 'events_carbon_footprint_report_download', methods: ['GET'])]
class DownloadReportController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        EventCarbonFootprintReportRepository $reportRepository,
        DownloadEventCarbonFootprintReportService $downloadReport,
        LoggedUser $loggedUser,
    ): Response {
        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $report = $reportRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id())
            ?? throw $this->createNotFoundException('Informe no encontrado');

        try {
            $response = ($downloadReport)(
                new DownloadEventCarbonFootprintReportRequest($report->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadEventCarbonFootprintReportResponse $response): StreamedResponse
    {
        $stream = $response->stream;

        $streamedResponse = new StreamedResponse(static function () use ($stream): void {
            $output = fopen('php://output', 'wb');

            if (false !== $output) {
                stream_copy_to_stream($stream, $output);
                fclose($output);
            }

            fclose($stream);
        });

        $streamedResponse->headers->set('Content-Type', 'application/pdf');
        $streamedResponse->headers->set(
            'Content-Disposition',
            ContentDisposition::attachment($response->filename),
        );

        return $streamedResponse;
    }
}
