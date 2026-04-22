<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Register;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterPropertyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private PropertyRepository $propertyRepository, private CompanyRepository $companyRepository, private PropertyAuthorizer $authorizer) {}

    public function __invoke(RegisterPropertyRequest $request, User $user): RegisterPropertyResponse
    {
        $id = PropertyId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
            accountId: $request->accountId,
        ), Action::Register, $user);

        $companyId = $request->companyId;

        if (null !== $companyId) {
            if (null === $request->accountId) {
                throw new \InvalidArgumentException('Cannot assign a company to a property without an account');
            }

            $company = $this->companyRepository->find($companyId);

            if (null === $company || null === $company->accountId() || !$company->accountId()->equals($request->accountId)) {
                throw new \InvalidArgumentException('Company does not belong to the same account as the property');
            }
        }

        $property = new Property($id);
        $property->register(
            name: $request->name,
            accountId: $request->accountId,
            info: $request->info,
            externalId: $request->externalId,
            companyId: $companyId,
        );

        $this->propertyRepository->add($property);

        return new RegisterPropertyResponse(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId(),
            info: $property->info(),
            externalId: $property->externalId(),
            companyId: $property->companyId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPropertyRequest;
    }
}
