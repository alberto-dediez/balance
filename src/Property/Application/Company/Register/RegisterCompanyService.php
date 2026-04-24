<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Register;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Address;

class RegisterCompanyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanyAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCompanyRequest $request, User $user): RegisterCompanyResponse
    {
        $id = CompanyId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Company(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $address = new Address(
            street: $request->street ?: null,
            postalCode: $request->postalCode ?: null,
            city: $request->city ?: null,
            country: $request->country ?: null,
        );

        $company = new Company($id);
        $company->register(
            name: $request->name,
            vatNumber: $request->vatNumber,
            address: $address,
            accountId: $request->accountId,
        );

        $this->companyRepository->add($company);

        return new RegisterCompanyResponse(
            companyId: $company->id(),
            name: $company->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCompanyRequest;
    }
}
