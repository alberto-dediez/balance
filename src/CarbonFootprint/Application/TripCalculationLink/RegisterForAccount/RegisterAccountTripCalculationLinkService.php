<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class RegisterAccountTripCalculationLinkService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationLinkAuthorizer $tripCalculationLinkAuthorizer,
        private CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function __invoke(RegisterAccountTripCalculationLinkRequest $request, User $user): RegisterAccountTripCalculationLinkResponse
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        if (null === $accountId) {
            throw new \InvalidArgumentException('No current account');
        }

        $this->tripCalculationLinkAuthorizer->denyUnlessCanDoThat(
            new TripCalculationLink(id: TripCalculationLinkId::generate(), accountId: $accountId),
            Action::Register,
            $user,
        );

        $existing = $this->tripCalculationLinkRepository->findForAccount($accountId);

        if (null !== $existing) {
            return new RegisterAccountTripCalculationLinkResponse($existing);
        }

        $tripCalculationLink = new TripCalculationLink(TripCalculationLinkId::generate());
        $tripCalculationLink->registerForAccount(
            accountId: $accountId,
            token: TripCalculationLink::generateToken(),
        );

        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return new RegisterAccountTripCalculationLinkResponse($tripCalculationLink);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAccountTripCalculationLinkRequest;
    }
}
