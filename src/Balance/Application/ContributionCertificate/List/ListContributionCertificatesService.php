<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\List;

use App\Balance\Application\ContributionCertificate\ContributionCertificateAuthorizer;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListContributionCertificatesService
{
    public function __construct(
        private ContributionCertificateRepository $repository,
        private ContributionCertificateAuthorizer $authorizer,
    ) {}

    public function __invoke(ListContributionCertificatesRequest $request, User $user): ListContributionCertificatesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ContributionCertificate(id: ContributionCertificateId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        return new ListContributionCertificatesResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
