<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Register;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\DuplicatedPayeeSubjectException;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class RegisterPayeeService
{
    public function __construct(
        private PayeeRepository $payeeRepository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterPayeeRequest $request, User $user): RegisterPayeeResponse
    {
        $type = $request->type ?? throw new \InvalidArgumentException('A payee type is required.');
        $subjectId = $request->subjectId ?? throw new \InvalidArgumentException('A payee subject is required.');

        $payee = new Payee(id: PayeeId::generate(), type: $type, subjectId: $subjectId);

        $this->authorizer->denyUnlessCanDoThat($payee, SecurityAction::Register, $user);

        $this->denyDuplicatedSubject($type, $subjectId);

        $payee->register(
            type: $type,
            subjectId: $subjectId,
            legalName: $request->legalName,
            taxId: $request->taxId,
            address: $request->address(),
            iban: $request->iban,
            bic: $request->bic,
            holderName: $request->holderName,
            notes: $request->notes,
        );

        $this->payeeRepository->add($payee);

        return new RegisterPayeeResponse(
            payeeId: $payee->id(),
            legalName: $payee->legalName(),
            status: $payee->status(),
        );
    }

    private function denyDuplicatedSubject(PayeeType $type, string $subjectId): void
    {
        if (null !== $this->payeeRepository->findOneBySubject($type, $subjectId)) {
            throw new DuplicatedPayeeSubjectException($type, $subjectId);
        }
    }
}
