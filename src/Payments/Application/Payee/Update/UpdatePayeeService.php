<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Update;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class UpdatePayeeService
{
    public function __construct(
        private PayeeRepository $payeeRepository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdatePayeeRequest $request, User $user): UpdatePayeeResponse
    {
        $payee = $this->payeeRepository->find($request->payeeId);

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payee, SecurityAction::Edit, $user);

        $payee->update(
            legalName: $request->legalName,
            taxId: $request->taxId,
            address: $request->address(),
            iban: $request->iban,
            bic: $request->bic,
            holderName: $request->holderName,
            notes: $request->notes,
        );

        $this->payeeRepository->add($payee);

        return new UpdatePayeeResponse(payeeId: $payee->id(), status: $payee->status());
    }
}
