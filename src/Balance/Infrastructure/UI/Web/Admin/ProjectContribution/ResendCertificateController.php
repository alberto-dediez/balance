<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ProjectContribution;

use App\Balance\Application\ContributionCertificate\ContributionCertificateAuthorizer;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/admin/contributions/{projectContributionId}/certificate/resend',
    name: 'admin_project_contributions_certificate_resend',
    methods: ['POST'],
)]
class ResendCertificateController extends AbstractController
{
    public function __invoke(
        string $projectContributionId,
        ContributionCertificateRepository $contributionCertificateRepository,
        ContributionCertificateAuthorizer $authorizer,
        SendContributionCertificateService $sendContributionCertificate,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $certificate = $contributionCertificateRepository->findByProjectContributionId(
            new ProjectContributionId($projectContributionId),
        ) ?? throw $this->createNotFoundException('Certificado no encontrado');

        try {
            $authorizer->denyUnlessCanDoThat($certificate, Action::View, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if (!$this->isCsrfTokenValid('certificate_resend_' . $projectContributionId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $email = trim($request->request->getString('email'));

        try {
            $sendContributionCertificate(new SendContributionCertificateRequest(
                contributionCertificateId: $certificate->id(),
                email: '' === $email ? null : $email,
            ));

            $this->addFlash('success', new TranslatableMessage('Certificado enviado.'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('No hemos podido enviar el certificado a esa dirección.'));
        }

        return $this->redirectToRoute('admin_project_contributions_view', ['projectContributionId' => $projectContributionId]);
    }
}
