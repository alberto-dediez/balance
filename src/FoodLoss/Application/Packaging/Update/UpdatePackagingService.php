<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Update;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdatePackagingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PackagingRepository $packagingRepository,
        private PackagingAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdatePackagingRequest $request, User $user): UpdatePackagingResponse
    {
        $packaging = $this->packagingRepository->find($request->packagingId);

        if (null == $packaging) {
            throw new \InvalidArgumentException('Packaging not found');
        }

        $this->authorizer->denyUnlessCanDoThat($packaging, SecurityAction::Edit, $user);

        $packaging->update(
            name: $request->name,
            packagingType: $request->packagingType,
        );

        $requestedIds = array_map(fn($s) => (string) $s->propertyId, $request->stocks);

        $toRemove = [];
        foreach ($packaging->stocks()->toArray() as $existingStock) {
            if (!in_array((string) $existingStock->propertyId(), $requestedIds, true)) {
                $toRemove[] = $existingStock->propertyId();
            }
        }
        foreach ($toRemove as $propertyId) {
            $packaging->removePropertyStock($propertyId);
        }

        foreach ($request->stocks as $stockData) {
            if ($stockData->stock > 0) {
                $packaging->setPropertyStock($stockData->propertyId, $stockData->stock);
            } else {
                $packaging->removePropertyStock($stockData->propertyId);
            }
        }

        $this->packagingRepository->add($packaging);

        return new UpdatePackagingResponse(
            packagingId: $packaging->id(),
            name: $packaging->name(),
            packagingType: $packaging->packagingType(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePackagingRequest;
    }
}
