<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Register;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterPackagingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PackagingRepository $packagingRepository,
        private PackagingAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterPackagingRequest $request, User $user): RegisterPackagingResponse
    {
        if (null == $request->accountId) {
            throw new \InvalidArgumentException('AccountId is required');
        }

        $id = PackagingId::generate();

        $packaging = new Packaging(id: $id, accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($packaging, SecurityAction::List, $user);

        $packaging->register(
            name: $request->name,
            packagingType: $request->packagingType,
            accountId: $request->accountId,
        );

        foreach ($request->stocks as $stockData) {
            if ($stockData->stock > 0) {
                $packaging->setPropertyStock($stockData->propertyId, $stockData->stock);
            }
        }

        $this->packagingRepository->add($packaging);

        return new RegisterPackagingResponse(
            packagingId: $packaging->id(),
            name: $packaging->name(),
            packagingType: $packaging->packagingType(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPackagingRequest;
    }
}
