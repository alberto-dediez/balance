<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ProjectContribution;

use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/admin/contributions/{projectContributionId}/certificate/download',
    name: 'admin_project_contributions_certificate_download',
    methods: ['GET'],
)]
class DownloadCertificateController extends AbstractController
{
    public function __invoke(
        string $projectContributionId,
        ContributionCertificateRepository $contributionCertificateRepository,
        DownloadContributionCertificateService $downloadContributionCertificate,
        LoggedUser $loggedUser,
    ): Response {
        $certificate = $contributionCertificateRepository->findByProjectContributionId(
            new ProjectContributionId($projectContributionId),
        ) ?? throw $this->createNotFoundException('Certificado no encontrado');

        try {
            $response = $downloadContributionCertificate(
                new DownloadContributionCertificateRequest($certificate->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadContributionCertificateResponse $response): StreamedResponse
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
        $streamedResponse->headers->set(
            'Content-Disposition',
            ContentDisposition::attachment($response->filename),
        );

        return $streamedResponse;
    }
}
