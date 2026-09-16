<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Register;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterPropertyCarbonFootprintService implements CommandHandler
{
    use CommandInvokeHandlerTrait;
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterPropertyCarbonFootprintRequest $request, User $user): RegisterPropertyCarbonFootprintResponse
    {
        $id = PropertyCarbonFootprintId::generate();

        $this->authorizer->denyUnlessCanDoThat(new PropertyCarbonFootprint(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $propertyId = $request->propertyId
            ?? throw new \InvalidArgumentException('Property not found in account');

        $accountId = $request->accountId
            ?? throw new \InvalidArgumentException('Property not found in account');

        $emissionFactorGroupId = $request->emissionFactorGroupId
            ?? throw new \InvalidArgumentException('No emission factor group configured');

        $property = $this->propertyRepository->find($propertyId);

        if (null === $property || $property->isRemoved() || true !== $property->accountId()?->equals($accountId)) {
            throw new \InvalidArgumentException('Property not found in account');
        }

        if (null !== $this->propertyCarbonFootprintRepository->findByPropertyIdAndYear($propertyId, $request->year)) {
            throw new PropertyCarbonFootprintAlreadyRegisteredException($propertyId, $request->year);
        }

        $propertyCarbonFootprint = new PropertyCarbonFootprint($id);
        $propertyCarbonFootprint->register(
            propertyId: $propertyId,
            accountId: $accountId,
            emissionFactorGroupId: $emissionFactorGroupId,
            year: $request->year,
        );

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);

        return new RegisterPropertyCarbonFootprintResponse($propertyCarbonFootprint->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPropertyCarbonFootprintRequest;
    }
}
