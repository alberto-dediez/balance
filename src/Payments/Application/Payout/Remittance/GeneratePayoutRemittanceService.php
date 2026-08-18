<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remittance;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payout\PayeeNotPayableException;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutRemittanceGenerator;
use App\Payments\Domain\Model\Payout\PayoutRemittanceLine;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GeneratePayoutRemittanceService
{
    /** @param iterable<PayoutRemittanceGenerator> $generators */
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayeeRepository $payeeRepository,
        private PayoutAuthorizer $authorizer,
        private iterable $generators,
    ) {}

    public function __invoke(GeneratePayoutRemittanceRequest $request, User $user): GeneratePayoutRemittanceResponse
    {
        $payouts = $this->payouts($request);

        $this->authorizer->denyUnlessCanDoThat(new Payout(id: PayoutId::generate()), SecurityAction::Admin, $user);

        if ([] === $payouts) {
            throw new EmptyRemittanceException();
        }

        $generator = $this->generatorFor($request->format);
        $executionDate = $request->executionDate ?? new \DateTimeImmutable('NOW');

        $lines = array_map(fn(Payout $payout): PayoutRemittanceLine => $this->lineOf($payout), $payouts);

        return new GeneratePayoutRemittanceResponse(
            content: $generator->generate($lines, $executionDate),
            filename: sprintf('remesa-%s.%s', $executionDate->format('Ymd-His'), $generator->extension()),
            contentType: $generator->contentType(),
            linesCount: count($lines),
            totalAmount: round(array_sum(array_map(static fn(PayoutRemittanceLine $line): float => $line->amount, $lines)), 2),
        );
    }

    /** @return list<Payout> */
    private function payouts(GeneratePayoutRemittanceRequest $request): array
    {
        if ([] !== $request->payoutIds) {
            $payouts = [];

            foreach ($request->payoutIds as $payoutId) {
                $payout = $this->payoutRepository->find($payoutId);

                if (null !== $payout && $payout->status()->isPending()) {
                    $payouts[] = $payout;
                }
            }

            return $payouts;
        }

        return array_values($this->payoutRepository->findByFilter(new PayoutFilter(status: PayoutStatus::Draft, limit: 0)));
    }

    private function lineOf(Payout $payout): PayoutRemittanceLine
    {
        $payeeId = $payout->payeeId();
        $payee = $payeeId instanceof PayeeId ? $this->payeeRepository->find($payeeId) : null;

        if (null === $payee || !$payee->isReady() || null === $payee->iban()) {
            throw new PayeeNotPayableException((string) $payout->number());
        }

        return new PayoutRemittanceLine(
            endToEndId: (string) $payout->number(),
            amount: $payout->payableAmount(),
            currency: $payout->currency(),
            creditorName: $payee->holderName() ?? $payee->legalName(),
            iban: $payee->iban(),
            bic: $payee->bic(),
            concept: $this->conceptOf($payout, $payee->legalName()),
        );
    }

    private function conceptOf(Payout $payout, string $payeeName): string
    {
        $period = $payout->periodTo() instanceof \DateTimeImmutable
            ? $payout->periodTo()->format('m/Y')
            : $payout->createdAt()->format('m/Y');

        return sprintf('Liquidacion %s - %s - %s', (string) $payout->number(), $payeeName, $period);
    }

    private function generatorFor(string $format): PayoutRemittanceGenerator
    {
        foreach ($this->generators as $generator) {
            if ($generator->format() === $format) {
                return $generator;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown remittance format "%s".', $format));
    }
}
