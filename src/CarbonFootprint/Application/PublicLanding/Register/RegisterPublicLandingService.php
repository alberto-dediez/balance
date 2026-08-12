<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Register;

use App\CarbonFootprint\Application\PublicLanding\PublicLandingOwnerLocator;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

/** Idempotent: an owner always has exactly one landing, created empty and inactive the first time it is asked for. */
class RegisterPublicLandingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PublicLandingRepository $publicLandingRepository,
        private PublicLandingOwnerLocator $ownerLocator,
    ) {}

    public function __invoke(RegisterPublicLandingRequest $request, User $user): RegisterPublicLandingResponse
    {
        $this->ownerLocator->denyUnlessCanDoThat($request->ownerType, $request->ownerId, Action::Edit, $user);

        $existing = $this->publicLandingRepository->findByOwner($request->ownerType, $request->ownerId);

        if (null !== $existing) {
            return new RegisterPublicLandingResponse($existing);
        }

        $publicLanding = new PublicLanding(PublicLandingId::generate());
        $publicLanding->register(
            ownerType: $request->ownerType,
            ownerId: $request->ownerId,
            accountId: $this->ownerLocator->accountId($request->ownerType, $request->ownerId),
        );

        $this->publicLandingRepository->add($publicLanding);

        return new RegisterPublicLandingResponse($publicLanding);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPublicLandingRequest;
    }
}
