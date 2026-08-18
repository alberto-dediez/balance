<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCertificateTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compensate/{origin}/{originId}/{contributionId}/certificate', name: 'owner_contribution_certificate', methods: ['GET'])]
class CertificateController extends AbstractController
{
    use OwnerContributionTrait;
    use ContributionCertificateTrait;

    public function __invoke(
        string $origin,
        string $originId,
        string $contributionId,
        OwnerContributionContextResolver $contextResolver,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        DownloadContributionCertificateService $downloadContributionCertificate,
        LoggedUser $loggedUser,
    ): Response {
        $context = $this->resolveContext($origin, $originId, $contextResolver, $loggedUser());

        $certificate = $this->resolveCertificate(
            $context->accountId,
            $contributionId,
            $projectContributionRepository,
            $contributionCertificateRepository,
        );

        if (null === $certificate) {
            throw $this->createNotFoundException('Certificado no encontrado.');
        }

        try {
            $response = $downloadContributionCertificate(
                new DownloadContributionCertificateRequest($certificate->id()),
            );
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
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $response->filename),
        );

        return $streamedResponse;
    }
}
