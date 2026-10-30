<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateResponse;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprint-certificates/{certificateId}/download', name: 'property_carbon_footprint_certificates_download', methods: ['GET'])]
class DownloadController extends AbstractController
{
    use GetPropertyCarbonFootprintCertificateFromIdTrait;

    public function __invoke(
        string $certificateId,
        PropertyCarbonFootprintCertificateRepository $certificateRepository,
        DownloadPropertyCarbonFootprintCertificateService $downloadCertificate,
        LoggedUser $loggedUser,
    ): Response {
        $certificate = $this->getPropertyCarbonFootprintCertificateFromId($certificateId, $certificateRepository);

        try {
            $response = ($downloadCertificate)(new DownloadPropertyCarbonFootprintCertificateRequest($certificate->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadPropertyCarbonFootprintCertificateResponse $response): StreamedResponse
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

        $streamedResponse->headers->set('Content-Type', $response->mimeType);
        $streamedResponse->headers->set('Content-Disposition', ContentDisposition::attachment($response->filename));

        return $streamedResponse;
    }
}
