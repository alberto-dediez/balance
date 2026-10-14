<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Remove;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCompanyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CompanyRepository $repository, private CompanyAuthorizer $authorizer) {}

    public function __invoke(RemoveCompanyRequest $request, User $user): RemoveCompanyResponse
    {
        $company = $this->repository->find($request->companyId);

        if (null == $company) {
            throw new \InvalidArgumentException('Company not found');
        }

        $this->authorizer->denyUnlessCanDoThat($company, SecurityAction::Remove, $user);

        $company->remove();

        $this->repository->add($company);

        return new RemoveCompanyResponse(
            companyId: $company->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCompanyRequest;
    }
}
