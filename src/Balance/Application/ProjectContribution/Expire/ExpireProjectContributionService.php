<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

/**
 * Releases the reservation of a contribution whose window is over, or whose checkout session the
 * gateway already closed: waiting for the window would hold the credit back for nothing. The
 * payment is checked first, so a lost webhook confirms the contribution instead of wrongly
 * expiring a paid one.
 */
class ExpireProjectContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private PaymentRepository $paymentRepository,
    ) {}

    public function __invoke(ExpireProjectContributionRequest $request, User $user): ExpireProjectContributionResponse
    {
        $now = $request->now ?? new \DateTimeImmutable('NOW');
        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        if (!$projectContribution->isPending()) {
            return new ExpireProjectContributionResponse(status: $projectContribution->status(), changed: false);
        }

        $paymentId = $projectContribution->paymentId();
        $payment = $paymentId instanceof PaymentId ? $this->paymentRepository->find($paymentId) : null;

        if (null !== $payment && $payment->isPaid()) {
            $projectContribution->pay();
            $this->projectContributionRepository->add($projectContribution);

            return new ExpireProjectContributionResponse(status: $projectContribution->status(), changed: true);
        }

        if (!($payment instanceof Payment && $payment->isExpired()) && !$projectContribution->hasExpired($now)) {
            return new ExpireProjectContributionResponse(status: $projectContribution->status(), changed: false);
        }

        $projectContribution->expire();
        $this->projectContributionRepository->add($projectContribution);

        return new ExpireProjectContributionResponse(status: $projectContribution->status(), changed: true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExpireProjectContributionRequest;
    }
}
