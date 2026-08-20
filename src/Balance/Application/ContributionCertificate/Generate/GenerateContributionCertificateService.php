<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStorage;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationUrlGenerator;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class GenerateContributionCertificateService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private ContributionCertificateRepository $contributionCertificateRepository,
        private ProjectRepository $projectRepository,
        private ContributionCertificatePdfGenerator $pdfGenerator,
        private ContributionCertificateStorage $storage,
        private ContributionCertificateVerificationUrlGenerator $verificationUrlGenerator,
        private BrandingResolver $brandingResolver,
        private CertificateLogoDownloader $logoDownloader,
        private string $localeList,
        private string $documentAuthor,
    ) {}

    public function __invoke(GenerateContributionCertificateRequest $request, User $user): void
    {
        $contributionCertificate = $this->contributionCertificateRepository->find($request->contributionCertificateId);

        if (null === $contributionCertificate) {
            throw new \InvalidArgumentException('ContributionCertificate not found');
        }

        if ($contributionCertificate->isGenerated()) {
            return;
        }

        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        $projectId = $contributionCertificate->projectId();
        $project = $projectId instanceof ProjectId ? $this->projectRepository->find($projectId) : null;

        $tokens = $this->brandingResolver->resolve($contributionCertificate->accountId());
        $logoPath = $this->logoDownloader->download($tokens->logoMediaId);
        $branding = PdfBranding::fromTokens($tokens, $this->documentAuthor, $logoPath);

        $previousLocale = LocaleGetter::locale();
        $files = [];

        try {
            foreach ($locales as $locale) {
                LocaleGetter::setLocale($locale);

                $data = $this->composeData($contributionCertificate, $project, $locale);

                $localPath = $this->pdfGenerator->generate($data, $locale, $branding);

                try {
                    $files[$locale->value] = $this->storage->store(
                        $contributionCertificate->id(),
                        $contributionCertificate->accountId(),
                        $locale,
                        $localPath,
                    );
                } finally {
                    @unlink($localPath);
                }
            }

            $contributionCertificate->markGenerated($files);
        } catch (\Throwable $e) {
            foreach ($files as $storedPath) {
                $this->storage->delete($storedPath);
            }

            $contributionCertificate->markFailed($e->getMessage());
            $this->contributionCertificateRepository->add($contributionCertificate);

            throw $e;
        } finally {
            LocaleGetter::setLocale($previousLocale);

            if (null !== $logoPath) {
                @unlink($logoPath);
            }
        }

        $this->contributionCertificateRepository->add($contributionCertificate);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof GenerateContributionCertificateRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GenerateContributionCertificateRequest;
    }

    private function composeData(
        ContributionCertificate $contributionCertificate,
        ?Project $project,
        Locale $locale,
    ): ContributionCertificateData {
        $projectName = $project instanceof Project && '' !== $project->name()
            ? $project->name()
            : $contributionCertificate->projectName();

        return new ContributionCertificateData(
            code: $contributionCertificate->code(),
            type: $contributionCertificate->type(),
            beneficiaryName: $contributionCertificate->beneficiaryName(),
            beneficiaryIsCompany: $contributionCertificate->beneficiaryIsCompany(),
            beneficiaryVatNumber: $contributionCertificate->beneficiaryVatNumber(),
            contactName: $contributionCertificate->contactName(),
            contactEmail: $contributionCertificate->contactEmail(),
            contributorCountry: $contributionCertificate->contributorCountry(),
            co2eqKg: $contributionCertificate->co2eqKg(),
            contributionAmount: $contributionCertificate->contributionAmount(),
            currency: $contributionCertificate->currency(),
            projectName: $projectName,
            projectLocation: $contributionCertificate->projectLocation(),
            projectRegistryId: $contributionCertificate->projectRegistryId(),
            masterSerials: $contributionCertificate->masterSerials(),
            creditLots: $contributionCertificate->creditLots(),
            issuedAt: $contributionCertificate->issuedAt(),
            verificationUrl: $this->verificationUrlGenerator->generate($contributionCertificate->verificationToken(), $locale),
            origin: $contributionCertificate->origin(),
            originName: $contributionCertificate->originName(),
        );
    }
}
