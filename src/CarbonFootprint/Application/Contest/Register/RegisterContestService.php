<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Register;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class RegisterContestService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private ContestHostLocator $hostLocator,
        private CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function __invoke(RegisterContestRequest $request, User $user): RegisterContestResponse
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();
        $contest = new Contest(id: ContestId::generate(), accountId: $accountId);

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Register, $user);

        assert(null !== $accountId);

        foreach ($request->hosts as $host) {
            $this->hostLocator->denyUnlessCanEdit($host, $user);

            if (!($this->hostLocator->accountId($host)?->equals($accountId) ?? false)) {
                throw new UnauthorizedActionException($contest, Action::Register);
            }
        }

        $contest->register(
            accountId: $accountId,
            participationMode: $request->participationMode,
            awardMode: $request->awardMode,
            winnersCount: $request->winnersCount,
            platformDraw: $request->platformDraw,
            adultsOnly: $request->adultsOnly,
            name: $request->name,
            description: $request->description,
            prizeDescription: $request->prizeDescription,
            terms: $request->terms,
            organizerName: $request->organizerName,
            organizerTaxId: $request->organizerTaxId,
            organizerContactEmail: $request->organizerContactEmail,
            privacyPolicyUrl: $request->privacyPolicyUrl,
            startsAt: $request->startsAt,
            endsAt: $request->endsAt,
            hosts: $request->hosts,
            allowedCountries: $request->allowedCountries,
        );

        $this->contestRepository->add($contest);

        return new RegisterContestResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterContestRequest;
    }
}
