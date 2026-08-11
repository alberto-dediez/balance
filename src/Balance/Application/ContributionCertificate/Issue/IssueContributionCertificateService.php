<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Application\ContributionCertificate\Generate\GenerateContributionCertificateRequest;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateCodeGenerator;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationTokenGenerator;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

/**
 * The only place a certificate is born. Everything the document says is copied here, so the paper
 * keeps its figures however the project, its price or its registry entry move afterwards.
 */
class IssueContributionCertificateService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContributionCertificateRepository $contributionCertificateRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectRepository $projectRepository,
        private DestinationRepository $destinationRepository,
        private ContributionCertificateCodeGenerator $codeGenerator,
        private ContributionCertificateVerificationTokenGenerator $verificationTokenGenerator,
    ) {}

    public function __invoke(IssueContributionCertificateRequest $request, User $user): IssueContributionCertificateResponse
    {
        $existing = $this->contributionCertificateRepository->findByProjectContributionId($request->projectContributionId);

        if (null !== $existing) {
            return new IssueContributionCertificateResponse(contributionCertificate: $existing, issued: false);
        }

        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        if (!$projectContribution->isPaid()) {
            throw new \InvalidArgumentException('Project contribution is not paid');
        }

        $accountId = $projectContribution->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('Project contribution has no account');
        }

        $projectId = $projectContribution->projectId();
        $project = $projectId instanceof ProjectId ? $this->projectRepository->find($projectId) : null;

        if (!$project instanceof Project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $type = ContributionCertificateType::fromProjectType($project->projectType());

        $contributionCertificate = new ContributionCertificate(ContributionCertificateId::generate());

        $contributionCertificate->issue(
            accountId: $accountId,
            projectContributionId: $projectContribution->id(),
            code: $this->codeGenerator->generate(),
            verificationToken: $this->verificationTokenGenerator->generate(),
            type: $type,
            locale: $request->locale ?? LocaleGetter::locale(),
            beneficiaryName: $this->beneficiaryName($projectContribution),
            beneficiaryIsCompany: $projectContribution->contributorIsCompany(),
            beneficiaryVatNumber: $projectContribution->contributorVatNumber(),
            contactName: $projectContribution->contributorName(),
            contactEmail: $projectContribution->contributorEmail(),
            contributorCountry: $projectContribution->contributorCountry(),
            co2eqKg: $projectContribution->co2eqKg(),
            tonnes: $projectContribution->tonnes(),
            contributionAmount: $projectContribution->contributionAmount(),
            currency: $projectContribution->currency(),
            projectId: $projectId,
            projectName: $project->name(),
            projectLocation: $this->projectLocation($project),
            projectRegistryId: $project->mitecoId(),
            masterSerials: $projectContribution->masterSerials(),
        );

        $this->contributionCertificateRepository->add($contributionCertificate);

        // Drawing the PDFs is a second deferred hop, so a retried issue never redraws documents
        // that are already on disk.
        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateContributionCertificateRequest($contributionCertificate->id()),
                priority: Priority::LOW,
            ),
            $user,
        );

        return new IssueContributionCertificateResponse(contributionCertificate: $contributionCertificate, issued: true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof IssueContributionCertificateRequest;
    }

    /** A company is the one that receives the document; the person who paid stays on it as contact. */
    private function beneficiaryName(ProjectContribution $projectContribution): string
    {
        if (!$projectContribution->contributorIsCompany()) {
            return $projectContribution->contributorName();
        }

        $companyName = $projectContribution->contributorCompanyName();

        return null !== $companyName && '' !== $companyName ? $companyName : $projectContribution->contributorName();
    }

    private function projectLocation(Project $project): ?string
    {
        $location = $project->location();

        if (!$location instanceof DestinationId) {
            return null;
        }

        return $this->destinationRepository->find($location)?->name();
    }
}
