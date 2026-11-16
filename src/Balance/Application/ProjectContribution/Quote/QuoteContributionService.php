<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\CreditPurchase\Balance\CreditSource;
use App\Balance\Application\CreditPurchase\Balance\CreditSourceSelector;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmounts;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;

final class QuoteContributionService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AccountProjectRepository $accountProjectRepository,
        private ContributionCalculator $contributionCalculator,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
        private CreditSourceSelector $creditSourceSelector,
    ) {}

    public function __invoke(QuoteContributionRequest $request): QuoteContributionResponse
    {
        if (!$this->accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $request->accountId)) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $now = $request->now ?? new \DateTimeImmutable('NOW');
        $project = $this->projectRepository->find($request->projectId);

        if (!$project instanceof Project || $project->isRemoved()) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $limitedByCredit = ProjectType::EmissionCompensation === $project->projectType();

        if (!$this->hasAdhered($request) && !($limitedByCredit && $this->hasSharedCandidate($request, $now))) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $pricePerTonne = $project->pricePerTonne();
        $costPerTonne = $project->costPerTonne();

        if (null === $pricePerTonne || $pricePerTonne <= 0.0 || null === $costPerTonne) {
            throw new ProjectNotContributableException($request->projectId);
        }

        $amounts = $this->amounts($request, $pricePerTonne, $costPerTonne);
        $availableTonnes = \INF;
        $creditAccountId = $request->accountId;

        if ($limitedByCredit) {
            $source = $this->creditSourceSelector->select(
                accountId: $request->accountId,
                projectId: $request->projectId,
                allowShared: $request->allowSharedCredit,
                propertyId: $request->propertyId,
                tonnes: $amounts->tonnes,
                now: $now,
            );

            $availableTonnes = $this->creditSourceSelector->maxAvailableTonnes(
                accountId: $request->accountId,
                projectId: $request->projectId,
                allowShared: $request->allowSharedCredit,
                propertyId: $request->propertyId,
                now: $now,
            );

            $creditAccountId = $source instanceof CreditSource ? $source->accountId : $request->accountId;
        }

        return new QuoteContributionResponse(
            project: $project,
            amounts: $amounts,
            limitedByCredit: $limitedByCredit,
            availableTonnes: $availableTonnes,
            creditAccountId: $creditAccountId,
        );
    }

    private function hasAdhered(QuoteContributionRequest $request): bool
    {
        return [] !== $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, projectId: $request->projectId, limit: 1),
        );
    }

    private function hasSharedCandidate(QuoteContributionRequest $request, \DateTimeImmutable $now): bool
    {
        if (!$request->allowSharedCredit) {
            return false;
        }

        $candidates = $this->creditSourceSelector->candidates(
            accountId: $request->accountId,
            projectId: $request->projectId,
            allowShared: true,
            propertyId: $request->propertyId,
            now: $now,
        );

        foreach ($candidates as $candidate) {
            if ($candidate->shared) {
                return true;
            }
        }

        return false;
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
