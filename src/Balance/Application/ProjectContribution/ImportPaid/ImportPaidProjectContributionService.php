<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\ImportPaid;

use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\DuplicatedProjectContributionException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;

class ImportPaidProjectContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportPaidProjectContributionRequest;
    }

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectRepository $projectRepository,
        private ProjectContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportPaidProjectContributionRequest $request, User $user): ImportPaidProjectContributionResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ProjectContribution(id: $request->projectContributionId, accountId: $request->accountId),
            SecurityAction::Edit,
            $user,
        );

        if ($this->projectContributionRepository->find($request->projectContributionId) instanceof ProjectContribution) {
            throw new DuplicatedProjectContributionException($request->projectContributionId);
        }

        $project = $this->projectRepository->find($request->projectId);

        if (null === $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $pricePerTonne = $project->pricePerTonne();

        if (null === $pricePerTonne || $pricePerTonne <= 0.0) {
            throw new \InvalidArgumentException('Project has no price per tonne');
        }

        $tonnes = round($request->amount / $pricePerTonne, 6);

        $projectContribution = new ProjectContribution(id: $request->projectContributionId);
        $projectContribution->register(
            accountId: $request->accountId,
            projectId: $request->projectId,
            origin: ProjectContributionOrigin::TripCalculation,
            originId: null,
            originName: null,
            co2eqKg: $tonnes * 1000,
            tonnes: $tonnes,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $project->costPerTonne(),
            freeAmount: true,
            contributionAmount: $request->amount,
            managementFeeAmount: 0.0,
            taxableAmount: 0.0,
            vatRate: 0.0,
            vatAmount: 0.0,
            totalAmount: $request->amount,
            vatReason: null,
            currency: 'EUR',
            contributorName: $request->contributorName,
            contributorEmail: $request->contributorEmail,
            contributorCountry: 'ES',
            contributorIsCompany: false,
            contributorCompanyName: null,
            contributorVatNumber: null,
            locale: Locale::Spanish,
            expiresAt: null,
            funding: ProjectContributionFunding::Imported,
            registeredAt: $request->paidAt,
        );

        $projectContribution->importAsPaid($request->paidAt);

        $this->projectContributionRepository->add($projectContribution);

        return new ImportPaidProjectContributionResponse(
            projectContributionId: $projectContribution->id(),
            tonnes: $projectContribution->tonnes(),
        );
    }
}
