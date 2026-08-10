<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmounts;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;

/**
 * Recomputes the breakdown from the project and the emissions on every request:
 * nothing the guest sees or pays is ever taken from the submitted form.
 */
final class QuoteContributionService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AccountProjectRepository $accountProjectRepository,
        private CreditAvailability $creditAvailability,
        private ContributionCalculator $contributionCalculator,
    ) {}

    public function __invoke(QuoteContributionRequest $request): QuoteContributionResponse
    {
        $now = $request->now ?? new \DateTimeImmutable('NOW');
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

        if (null === $pricePerTonne || $pricePerTonne <= 0.0 || null === $costPerTonne) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $limitedByCredit = ProjectType::EmissionCompensation === $project->projectType();
        $availableTonnes = \INF;

        if ($limitedByCredit) {
            $availableTonnes = $this->creditAvailability
                ->for($request->accountId, $request->projectId, $now)
                ->availableTonnes()
            ;
        }

        return new QuoteContributionResponse(
            project: $project,
            amounts: $this->amounts($request, $pricePerTonne, $costPerTonne),
            limitedByCredit: $limitedByCredit,
            availableTonnes: $availableTonnes,
        );
    }

    private function amounts(
        QuoteContributionRequest $request,
        float $pricePerTonne,
        float $costPerTonne,
    ): ContributionAmounts {
        if (null !== $request->freeAmount) {
            return $this->contributionCalculator->fromFreeAmount(
                amount: $request->freeAmount,
                pricePerTonne: $pricePerTonne,
                costPerTonne: $costPerTonne,
                countryCode: $request->contributorCountry,
                isCompany: $request->contributorIsCompany,
                vatNumber: $request->contributorVatNumber,
            );
        }

        if (null === $request->co2eqKg) {
            throw new \InvalidArgumentException('Either the emissions to offset or a free amount is required.');
        }

        return $this->contributionCalculator->fromEmissions(
            co2eqKg: $request->co2eqKg,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            countryCode: $request->contributorCountry,
            isCompany: $request->contributorIsCompany,
            vatNumber: $request->contributorVatNumber,
        );
    }
}
