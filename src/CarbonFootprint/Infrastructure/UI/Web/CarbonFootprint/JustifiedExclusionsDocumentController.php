<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\DownloadJustifiedExclusionsDocumentRequest;
use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\DownloadJustifiedExclusionsDocumentService;
use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\JustifiedExclusionsRequireCompletedFootprintException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprints/{subjectType}/{carbonFootprintId}/justified-exclusions', name: 'carbon_footprint_justified_exclusions_download', requirements: ['subjectType' => 'property|company'], methods: ['GET'])]
class JustifiedExclusionsDocumentController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        DownloadJustifiedExclusionsDocumentService $downloadJustifiedExclusionsDocument,
        LoggedUser $loggedUser,
    ): BinaryFileResponse {
        try {
            $response = $downloadJustifiedExclusionsDocument(
                new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::from($subjectType), $carbonFootprintId),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (JustifiedExclusionsRequireCompletedFootprintException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Huella no encontrada');
        }

        $file = new BinaryFileResponse($response->path);
        $file->headers->set('Content-Type', 'application/pdf');
        $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->filename);
        $file->deleteFileAfterSend(true);

        return $file;
    }
}
