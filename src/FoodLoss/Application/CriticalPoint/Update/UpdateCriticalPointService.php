<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateCriticalPointService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CriticalPointRepository $criticalPointRepository,
        private PropertyRepository $propertyRepository,
        private CriticalPointAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateCriticalPointRequest $request, User $user): UpdateCriticalPointResponse
    {
        $criticalPoint = $this->criticalPointRepository->find($request->criticalPointId);

        if (null == $criticalPoint) {
            throw new \InvalidArgumentException('Critical point not found');
        }

        $this->authorizer->denyUnlessCanDoThat($criticalPoint, SecurityAction::Edit, $user);

        if (null == $request->propertyId) {
            throw new \InvalidArgumentException('PropertyId is required');
        }

        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $propertyAccountId = $property->accountId();
        $requestAccountId = $request->accountId;

        if (null == $propertyAccountId || null == $requestAccountId || !$propertyAccountId->equals($requestAccountId)) {
            throw new \InvalidArgumentException('Property not found or does not belong to your account');
        }

        $criticalPoint->update(
            name: $request->name,
            propertyId: $request->propertyId,
            supplyChainStage: $request->supplyChainStage,
            prevention: $request->prevention,
            humanDonation: $request->humanDonation,
            animalFeed: $request->animalFeed,
            transformation: $request->transformation,
            composting: $request->composting,
            serviceCategoryIds: $request->serviceCategoryIds,
        );

        $this->criticalPointRepository->add($criticalPoint);

        return new UpdateCriticalPointResponse(
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
        return $command instanceof UpdateCriticalPointRequest;
    }
}
