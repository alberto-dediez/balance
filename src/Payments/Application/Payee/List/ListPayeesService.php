<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\List;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPayeesService
{
    public function __construct(
        private PayeeRepository $repository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(ListPayeesRequest $request, User $user): ListPayeesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Payee(id: PayeeId::generate()), SecurityAction::List, $user);

        return new ListPayeesResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
