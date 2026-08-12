<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Send;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStorage;
use App\Shared\Domain\Model\Email\EmailAttachment;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendContributionCertificateService
{
    private const string TEMPLATE = '@balance/email/contribution-certificate.email.html.twig';

    public function __construct(
        private ContributionCertificateRepository $contributionCertificateRepository,
        private ContributionCertificateStorage $storage,
        private EmailSender $emailSender,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
    ) {}

    public function __invoke(SendContributionCertificateRequest $request): void
    {
        $contributionCertificate = $this->contributionCertificateRepository->find($request->contributionCertificateId);

        if (null === $contributionCertificate) {
            throw new \InvalidArgumentException('ContributionCertificate not found');
        }

        if (!$contributionCertificate->isGenerated()) {
            throw new \InvalidArgumentException('ContributionCertificate is not generated');
        }

        $email = $request->email ?? $contributionCertificate->contactEmail();

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid recipient email');
        }

        $locale = $request->locale ?? $contributionCertificate->locale();
        $path = $contributionCertificate->fileFor($locale);

        if (null === $path) {
            throw new \InvalidArgumentException('ContributionCertificate has no file');
        }

        $localPath = $this->copyToTemporaryFile($path);

        try {
            $this->deliver($contributionCertificate, $email, $locale, $localPath);
        } finally {
            @unlink($localPath);
        }

        $contributionCertificate->markSent($email);
        $this->contributionCertificateRepository->add($contributionCertificate);
    }

    private function deliver(
        ContributionCertificate $contributionCertificate,
        string $email,
        Locale $locale,
        string $localPath,
    ): void {
        $this->localeSwitcher->runWithLocale(
            $locale->value,
            function () use ($contributionCertificate, $email, $localPath): void {
                $this->emailSender->__invoke(
                    recipient: new EmailRecipient(name: $contributionCertificate->contactName(), email: $email),
                    subject: $this->subject($contributionCertificate),
                    template: self::TEMPLATE,
                    params: [
                        'certificate' => $contributionCertificate,
                        'contactName' => $contributionCertificate->contactName(),
                        'projectName' => $contributionCertificate->projectName(),
                        'isCompensation' => $contributionCertificate->isCompensation(),
                    ],
                    attachments: [
                        new EmailAttachment(
                            filename: sprintf('certificado-%s.pdf', $contributionCertificate->code()),
                            path: $localPath,
                        ),
                    ],
                );
            },
        );
    }

    private function subject(ContributionCertificate $contributionCertificate): string
    {
        return $contributionCertificate->isCompensation()
            ? $this->translator->trans('Tu certificado de compensación de carbono')
            : $this->translator->trans('Tu certificado de aportación');
    }

    private function copyToTemporaryFile(string $path): string
    {
        $localPath = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('contribution-certificate-', true));

        $source = $this->storage->read($path);
        $target = fopen($localPath, 'wb');

        if (false === $target) {
            fclose($source);

            throw new \RuntimeException(sprintf('Unable to write certificate to "%s"', $localPath));
        }

        stream_copy_to_stream($source, $target);

        fclose($source);
        fclose($target);

        return $localPath;
    }
}
