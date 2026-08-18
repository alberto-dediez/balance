<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\View;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class ViewPayeeService
{
    public function __construct(
        private PayeeRepository $repository,
        private PayeeAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewPayeeRequest $request, User $user): ViewPayeeResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Payee(id: PayeeId::generate()), SecurityAction::View, $user);

        return new ViewPayeeResponse($this->locate($request));
    }

    private function locate(ViewPayeeRequest $request): ?Payee
    {
        if ($request->payeeId instanceof PayeeId) {
            return $this->repository->find($request->payeeId);
        }

        if (null === $request->type || null === $request->subjectId) {
            return null;
        }

        return $this->repository->findOneBySubject($request->type, $request->subjectId);
    }
}
