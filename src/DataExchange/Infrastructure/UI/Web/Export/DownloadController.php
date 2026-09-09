<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Export;

use App\DataExchange\Application\Exportation\Download\DownloadExportationRequest;
use App\DataExchange\Application\Exportation\Download\DownloadExportationResponse;
use App\DataExchange\Application\Exportation\Download\DownloadExportationService;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{code}/export/{id}/download', name: 'export_download')]
class DownloadController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        string $id,
        LoggedUser $loggedUser,
        DownloadExportationService $downloadExportation,
    ): Response {
        $exportationType = $this->getExportationTypeOrNotFound($code);

        $exportation = $this->getExportationOrNotFound($id);

        if ($exportation->type() !== $exportationType->code()) {
            throw $this->createNotFoundException();
        }

        try {
            $response = $downloadExportation(
                new DownloadExportationRequest($exportation->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        } catch (NotExportedException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadExportationResponse $response): StreamedResponse
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

        $streamedResponse->headers->set('Content-Type', $response->format->mimeType());
        $streamedResponse->headers->set(
            'Content-Disposition',
            ContentDisposition::attachment($response->filename),
        );

        return $streamedResponse;
    }
}
