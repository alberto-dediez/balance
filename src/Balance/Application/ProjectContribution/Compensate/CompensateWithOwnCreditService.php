<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Compensate;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationService;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class CompensateWithOwnCreditService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function isHandled(Command $command): bool
    {
        return $command instanceof CompensateWithOwnCreditRequest;
    }

    public function __construct(
        private QuoteOwnerCompensationService $quoteOwnerCompensation,
        private ProjectContributionRepository $projectContributionRepository,
        private AllocateContributionCreditsService $allocateContributionCredits,
        private CreditReservationLock $creditReservationLock,
        private ProjectContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(CompensateWithOwnCreditRequest $request, User $user): CompensateWithOwnCreditResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ProjectContribution(id: $request->projectContributionId, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        $projectContribution = $this->creditReservationLock->withLock(
            $request->accountId,
            $request->projectId,
            fn(): ProjectContribution => $this->compensate($request),
        );

        $this->allocateContributionCredits->__invoke(
            new AllocateContributionCreditsRequest($projectContribution->id()),
        );

        return new CompensateWithOwnCreditResponse(
            projectContributionId: $projectContribution->id(),
            tonnes: $projectContribution->tonnes(),
            contributionAmount: $projectContribution->contributionAmount(),
        );
    }

    private function compensate(CompensateWithOwnCreditRequest $request): ProjectContribution
    {
        $quote = ($this->quoteOwnerCompensation)(new QuoteOwnerCompensationRequest(
            accountId: $request->accountId,
            projectId: $request->projectId,
            co2eqKg: $request->co2eqKg,
            now: $request->now,
        ));

        if ($quote->exceedsCredit) {
            throw new InsufficientProjectCreditException($request->projectId, $quote->tonnes, $quote->ownAvailableTonnes);
        }

        $projectContribution = new ProjectContribution(id: $request->projectContributionId);
        $projectContribution->register(
            accountId: $request->accountId,
            projectId: $request->projectId,
            origin: $request->origin,
            originId: $request->originId,
            originName: $request->originName,
            co2eqKg: $quote->co2eqKg,
            tonnes: $quote->tonnes,
            pricePerTonne: $quote->pricePerTonne,
            costPerTonne: $quote->costPerTonne,
            freeAmount: false,
            contributionAmount: $quote->contributionAmount,
            managementFeeAmount: 0.0,
            taxableAmount: 0.0,
            vatRate: 0.0,
            vatAmount: 0.0,
            totalAmount: 0.0,
            vatReason: null,
            currency: 'EUR',
            contributorName: $request->beneficiaryName,
            contributorEmail: $request->beneficiaryEmail,
            contributorCountry: strtoupper($request->beneficiaryCountry),
            contributorIsCompany: $request->beneficiaryIsCompany,
            contributorCompanyName: $request->beneficiaryCompanyName,
            contributorVatNumber: $request->beneficiaryVatNumber,
            locale: $request->locale ?? LocaleGetter::locale(),
            expiresAt: null,
            funding: ProjectContributionFunding::OwnCredit,
        );

        $projectContribution->pay();

        $this->projectContributionRepository->add($projectContribution);

        return $projectContribution;
    }
}
