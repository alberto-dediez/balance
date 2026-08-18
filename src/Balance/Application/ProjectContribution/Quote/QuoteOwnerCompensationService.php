<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;

final class QuoteOwnerCompensationService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AccountProjectRepository $accountProjectRepository,
        private CreditAvailability $creditAvailability,
    ) {}

    public function __invoke(QuoteOwnerCompensationRequest $request): QuoteOwnerCompensationResponse
    {
        $project = $this->projectRepository->find($request->projectId);

        if (!$project instanceof Project || $project->isRemoved()) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $adhered = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, projectId: $request->projectId, limit: 1),
        );

        if (0 === count($adhered)) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $pricePerTonne = $project->pricePerTonne();
        $costPerTonne = $project->costPerTonne();

        if (
            ProjectType::EmissionCompensation !== $project->projectType()
            || null === $pricePerTonne
            || null === $costPerTonne
        ) {
            throw new ProjectNotContributableException($request->projectId);
        }

        if ($request->co2eqKg <= 0.0) {
            throw new \InvalidArgumentException('The emissions to offset must be greater than zero.');
        }

        $tonnes = round($request->co2eqKg / 1000, 6);
        $ownAvailableTonnes = $this->creditAvailability->ownFor($request->accountId, $request->projectId, $request->now);

        return new QuoteOwnerCompensationResponse(
            project: $project,
            tonnes: $tonnes,
            co2eqKg: $request->co2eqKg,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            contributionAmount: round($tonnes * $costPerTonne, 2),
            ownAvailableTonnes: $ownAvailableTonnes,
            exceedsCredit: $tonnes > $ownAvailableTonnes,
        );
    }
}
