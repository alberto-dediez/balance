<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Block;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class BlockPayeeService
{
    public function __construct(
        private PayeeRepository $payeeRepository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(BlockPayeeRequest $request, User $user): BlockPayeeResponse
    {
        $payee = $this->payeeRepository->find($request->payeeId);

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payee, SecurityAction::Edit, $user);

        $payee->block();

        $this->payeeRepository->add($payee);

        return new BlockPayeeResponse($payee->status());
    }
}
