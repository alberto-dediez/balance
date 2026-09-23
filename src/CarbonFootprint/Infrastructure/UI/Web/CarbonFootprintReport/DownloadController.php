<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportResponse;
use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint-reports/{reportId}/download', name: 'carbon_footprint_reports_download', methods: ['GET'])]
class DownloadController extends AbstractController
{
    use GetCarbonFootprintReportFromIdTrait;

    public function __invoke(
        string $reportId,
        CarbonFootprintReportRepository $reportRepository,
        DownloadCarbonFootprintReportService $downloadReport,
        LoggedUser $loggedUser,
    ): Response {
        $report = $this->getCarbonFootprintReportFromId($reportId, $reportRepository);

        try {
            $response = ($downloadReport)(new DownloadCarbonFootprintReportRequest($report->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadCarbonFootprintReportResponse $response): StreamedResponse
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
        $streamedResponse->headers->set('Content-Disposition', ContentDisposition::attachment($response->filename));

        return $streamedResponse;
    }
}
