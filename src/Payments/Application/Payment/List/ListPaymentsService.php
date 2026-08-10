<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\List;

use App\Payments\Application\Payment\PaymentAuthorizer;
use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPaymentsService
{
    public function __construct(
        private PaymentRepository $repository,
        private PaymentAuthorizer $authorizer,
    ) {}

    public function __invoke(ListPaymentsRequest $request, User $user): ListPaymentsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new Payment(id: PaymentId::generate()),
            SecurityAction::List,
            $user,
        );

        return new ListPaymentsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
