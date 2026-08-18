<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Unblock;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class UnblockPayeeService
{
    public function __construct(
        private PayeeRepository $payeeRepository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(UnblockPayeeRequest $request, User $user): UnblockPayeeResponse
    {
        $payee = $this->payeeRepository->find($request->payeeId);

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payee, SecurityAction::Edit, $user);

        $payee->unblock();

        $this->payeeRepository->add($payee);

        return new UnblockPayeeResponse($payee->status());
    }
}
