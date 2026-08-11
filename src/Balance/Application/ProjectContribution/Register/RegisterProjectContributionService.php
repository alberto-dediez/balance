<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Register;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionResponse;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionService;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmountTooLowException;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Payments\Application\Payment\Start\StartPaymentRequest;
use App\Payments\Application\Payment\Start\StartPaymentService;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;

/**
 * Public entry point of the compensation flow: prices the contribution, holds the credit back,
 * opens the payment and hands the guest the gateway URL.
 *
 * The hold is taken under a lock and committed before the gateway is called, so two guests can
 * never validate their availability against a credit the other one is already consuming.
 */
final class RegisterProjectContributionService
{
    public const SUBJECT_TYPE = 'balance.project_contribution';

    public const PAYMENT_START_FAILED = 'No se pudo abrir el pago de la aportación.';

    public function __construct(
        private QuoteContributionService $quoteContribution,
        private ProjectContributionRepository $projectContributionRepository,
        private StartPaymentService $startPayment,
        private SystemActorResolver $systemActor,
        private CreditReservationLock $creditReservationLock,
        private int $reservationHours = 12,
    ) {}

    public function __invoke(RegisterProjectContributionRequest $request): RegisterProjectContributionResponse
    {
        $now = $request->now ?? new \DateTimeImmutable('NOW');
        $id = $request->projectContributionId;
        $expiresAt = $now->modify(sprintf('+%d hours', $this->reservationHours));

        [$projectContribution, $quote] = $this->creditReservationLock->withLock(
            $request->accountId,
            $request->projectId,
            fn(): array => $this->reserve($request, $now, $expiresAt),
        );

        $amounts = $quote->amounts;

        try {
            $payment = $this->startPayment->__invoke(new StartPaymentRequest(
                subjectType: self::SUBJECT_TYPE,
                subjectId: (string) $id,
                description: $quote->project->name(),
                amount: $amounts->totalAmount,
                customerEmail: $request->contributorEmail,
                customerName: $request->contributorName,
                successUrl: $request->successUrl,
                cancelUrl: $request->cancelUrl,
                currency: $amounts->currency,
                expiresAt: $expiresAt,
            ));
        } catch (\Throwable $exception) {
            $projectContribution->fail(self::PAYMENT_START_FAILED);
            $this->projectContributionRepository->add($projectContribution);

            throw $exception;
        }

        $projectContribution->linkPayment($payment->paymentId);
        $this->projectContributionRepository->add($projectContribution);

        // Re-checked once the reservation window is over, so a guest who never came back from the
        // gateway stops holding the credit.
        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new ExpireProjectContributionRequest($id),
                priority: Priority::LOW,
                executeAt: $expiresAt,
            ),
            ($this->systemActor)(),
        );

        return new RegisterProjectContributionResponse(
            projectContributionId: $id,
            paymentId: $payment->paymentId,
            redirectUrl: $payment->redirectUrl,
            amounts: $amounts,
        );
    }

    /**
     * Quoting, checking the availability and writing the hold happen together: any of the three on
     * its own would leave the window where a concurrent guest reserves the same tonnes.
     *
     * @return array{ProjectContribution, QuoteContributionResponse}
     */
    private function reserve(
        RegisterProjectContributionRequest $request,
        \DateTimeImmutable $now,
        \DateTimeImmutable $expiresAt,
    ): array {
        $quote = $this->quoteContribution->__invoke(new QuoteContributionRequest(
            accountId: $request->accountId,
            projectId: $request->projectId,
            co2eqKg: $request->co2eqKg,
            freeAmount: $request->freeAmount,
            contributorCountry: $request->contributorCountry,
            contributorIsCompany: $request->contributorIsCompany,
            contributorVatNumber: $request->contributorVatNumber,
            now: $now,
        ));

        $amounts = $quote->amounts;

        if ($amounts->totalAmount < ContributionCalculator::MIN_TOTAL_AMOUNT) {
            throw new ContributionAmountTooLowException($amounts->totalAmount, ContributionCalculator::MIN_TOTAL_AMOUNT);
        }

        if ($quote->limitedByCredit && $amounts->tonnes > $quote->availableTonnes) {
            throw new InsufficientProjectCreditException($request->projectId, $amounts->tonnes, $quote->availableTonnes);
        }

        $projectContribution = new ProjectContribution(id: $request->projectContributionId);
        $projectContribution->register(
            accountId: $request->accountId,
            projectId: $request->projectId,
            origin: $request->origin,
            originId: $request->originId,
            co2eqKg: $amounts->co2eqKg,
            tonnes: $amounts->tonnes,
            pricePerTonne: $amounts->pricePerTonne,
            costPerTonne: $amounts->costPerTonne,
            freeAmount: $amounts->freeAmount,
            contributionAmount: $amounts->contributionAmount,
            managementFeeAmount: $amounts->managementFeeAmount,
            taxableAmount: $amounts->taxableAmount,
            vatRate: $amounts->vatRate,
            vatAmount: $amounts->vatAmount,
            totalAmount: $amounts->totalAmount,
            vatReason: $amounts->vatReason,
            currency: $amounts->currency,
            contributorName: $request->contributorName,
            contributorEmail: $request->contributorEmail,
            contributorCountry: strtoupper($request->contributorCountry),
            contributorIsCompany: $request->contributorIsCompany,
            contributorCompanyName: $request->contributorCompanyName,
            contributorVatNumber: $request->contributorVatNumber,
            expiresAt: $expiresAt,
        );

        $this->projectContributionRepository->add($projectContribution);

        return [$projectContribution, $quote];
    }
}
