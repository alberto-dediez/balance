<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCertificateTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compensate/{origin}/{originId}/{contributionId}/certificate/email', name: 'owner_contribution_certificate_email', methods: ['POST'])]
class CertificateEmailController extends AbstractController
{
    use OwnerContributionTrait;
    use ContributionCertificateTrait;

    public function __construct(
        private RateLimiterFactory $certificateResendLimiter,
    ) {}

    public function __invoke(
        string $origin,
        string $originId,
        string $contributionId,
        OwnerContributionContextResolver $contextResolver,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        SendContributionCertificateService $sendContributionCertificate,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $context = $this->resolveContext($origin, $originId, $contextResolver, $loggedUser());

        if (!$this->isCsrfTokenValid('owner_contribution_' . $originId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $certificate = $this->resolveCertificate(
            $context->accountId,
            $contributionId,
            $projectContributionRepository,
            $contributionCertificateRepository,
        );

        if (null === $certificate) {
            throw $this->createNotFoundException('Certificado no encontrado.');
        }

        $this->resendCertificate(
            $certificate,
            trim($request->request->getString('email')),
            $this->certificateResendLimiter,
            $sendContributionCertificate,
        );

        return $this->redirectToRoute('owner_contribution_confirmation', [
            'origin' => $origin,
            'originId' => $originId,
            'contributionId' => $contributionId,
        ]);
    }
}
