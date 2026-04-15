<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterCriticalPointService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CriticalPointRepository $criticalPointRepository,
        private PropertyRepository $propertyRepository,
        private CriticalPointAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCriticalPointRequest $request, User $user): RegisterCriticalPointResponse
    {
        $accountId = $request->accountId;

        if (null === $accountId) {
            throw new \InvalidArgumentException('User must have an account');
        }

        if (null == $request->propertyId) {
            throw new \InvalidArgumentException('PropertyId is required');
        }

        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property || null == $property->accountId() || !$property->accountId()->equals($accountId)) {
            throw new \InvalidArgumentException('Property not found or does not belong to your account');
        }

        $id = CriticalPointId::generate();

        $criticalPoint = new CriticalPoint(id: $id, accountId: $accountId);

        $this->authorizer->denyUnlessCanDoThat($criticalPoint, SecurityAction::List, $user);

        $criticalPoint->register(
            name: $request->name,
            propertyId: $request->propertyId,
            accountId: $accountId,
            supplyChainStage: $request->supplyChainStage,
            prevention: $request->prevention,
            humanDonation: $request->humanDonation,
            animalFeed: $request->animalFeed,
            transformation: $request->transformation,
            composting: $request->composting,
            serviceCategoryIds: $request->serviceCategoryIds,
        );

        $this->criticalPointRepository->add($criticalPoint);

        return new RegisterCriticalPointResponse(
            criticalPointId: $criticalPoint->id(),
            name: $criticalPoint->name(),
            serviceCategoryIds: $criticalPoint->serviceCategoryIds(),
            prevention: $criticalPoint->prevention(),
            humanDonation: $criticalPoint->humanDonation(),
            animalFeed: $criticalPoint->animalFeed(),
            transformation: $criticalPoint->transformation(),
            composting: $criticalPoint->composting(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCriticalPointRequest;
    }
}
