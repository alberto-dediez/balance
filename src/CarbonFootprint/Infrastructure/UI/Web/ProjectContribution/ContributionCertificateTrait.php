<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Translation\TranslatableMessage;

trait ContributionCertificateTrait
{
    private function resolveCertificate(
        AccountId $accountId,
        string $contributionId,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
    ): ?ContributionCertificate {
        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $contributionAccountId = $projectContribution?->accountId();

        if (
            null === $projectContribution
            || !$contributionAccountId instanceof AccountId
            || !$accountId->equals($contributionAccountId)
        ) {
            return null;
        }

        return $contributionCertificateRepository->findByProjectContributionId($projectContribution->id());
    }

    private function resendCertificate(
        ContributionCertificate $certificate,
        string $email,
        RateLimiterFactory $certificateResendLimiter,
        SendContributionCertificateService $sendContributionCertificate,
    ): void {
        $limiter = $certificateResendLimiter->create((string) $certificate->id());

        if ($limiter->consume(0)->getRemainingTokens() <= 0) {
            $this->addFlash('warning', new TranslatableMessage('Acabamos de enviarte el certificado. Espera un minuto antes de volver a pedirlo.'));

            return;
        }

        try {
            $sendContributionCertificate(new SendContributionCertificateRequest(
                contributionCertificateId: $certificate->id(),
                email: '' === $email ? null : $email,
                locale: LocaleGetter::locale(),
            ));
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('No hemos podido enviar el certificado a esa dirección.'));

            return;
        }

        $limiter->consume(1);

        $this->addFlash('success', new TranslatableMessage('Te hemos enviado el certificado por correo.'));
    }
}
