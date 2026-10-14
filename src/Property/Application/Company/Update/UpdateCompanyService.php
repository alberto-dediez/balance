<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Update;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Address;

class UpdateCompanyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanyAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateCompanyRequest $request, User $user): UpdateCompanyResponse
    {
        $company = $this->companyRepository->find($request->companyId);

        if (null == $company) {
            throw new \InvalidArgumentException('Company not found');
        }

        $this->authorizer->denyUnlessCanDoThat($company, SecurityAction::Edit, $user);

        $address = new Address(
            street: $request->street ?: null,
            postalCode: $request->postalCode ?: null,
            city: $request->city ?: null,
            country: $request->country ?: null,
        );

        $company->update(
            name: $request->name,
            vatNumber: $request->vatNumber,
            address: $address,
        );

        $this->companyRepository->add($company);

        return new UpdateCompanyResponse(
            companyId: $company->id(),
            name: $company->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateCompanyRequest;
    }
}
