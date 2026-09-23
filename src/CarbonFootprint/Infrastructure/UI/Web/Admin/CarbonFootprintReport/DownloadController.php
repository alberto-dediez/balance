<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport\GetCarbonFootprintReportFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/carbon-footprint-reports/{reportId}/download', name: 'admin_carbon_footprint_reports_download', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
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
