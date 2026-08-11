<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Download;

use App\Balance\Application\ContributionCertificate\ContributionCertificateAuthorizer;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStorage;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class DownloadContributionCertificateService
{
    public function __construct(
        private ContributionCertificateRepository $contributionCertificateRepository,
        private ContributionCertificateAuthorizer $authorizer,
        private ContributionCertificateStorage $storage,
    ) {}

    /**
     * A logged user goes through the authorizer. A guest reaching this from the public flow arrives
     * with no user at all: there the link token has already proved they may see it.
     */
    public function __invoke(DownloadContributionCertificateRequest $request, ?User $user = null): DownloadContributionCertificateResponse
    {
        $contributionCertificate = $this->contributionCertificateRepository->find($request->contributionCertificateId);

        if (null === $contributionCertificate) {
            throw new \InvalidArgumentException('ContributionCertificate not found');
        }

        if ($user instanceof User) {
            $this->authorizer->denyUnlessCanDoThat($contributionCertificate, SecurityAction::View, $user);
        }

        if (!$contributionCertificate->isGenerated()) {
            throw new \InvalidArgumentException('ContributionCertificate is not generated');
        }

        $locale = $request->locale ?? LocaleGetter::locale();
        $path = $contributionCertificate->fileFor($locale);

        if (null === $path) {
            throw new \InvalidArgumentException('ContributionCertificate has no file');
        }

        return new DownloadContributionCertificateResponse(
            stream: $this->storage->read($path),
            filename: sprintf('certificado-%s-%s.pdf', $contributionCertificate->code(), $locale->value),
        );
    }
}
