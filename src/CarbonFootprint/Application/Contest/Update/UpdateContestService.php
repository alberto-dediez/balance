<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Update;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Transaction\TransactionalSession;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateContestService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private ContestHostLocator $hostLocator,
        private ContestPublicationLock $publicationLock,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(UpdateContestRequest $request, User $user): UpdateContestResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Edit, $user);

        $addedHosts = [];

        foreach ($request->hosts as $host) {
            if ($contest->hasHost($host)) {
                continue;
            }

            $this->hostLocator->denyUnlessCanEdit($host, $user);

            $accountId = $contest->accountId();

            if (null === $accountId || !($this->hostLocator->accountId($host)?->equals($accountId) ?? false)) {
                throw new UnauthorizedActionException($contest, Action::Edit);
            }

            $addedHosts[] = $host;
        }

        if (!$contest->isPublished() || [] === $addedHosts) {
            $this->update($contest, $request);

            return new UpdateContestResponse($contest);
        }

        $this->transactionalSession->run(function () use ($contest, $request, $addedHosts): void {
            $accountId = $contest->accountId();

            if (null !== $accountId) {
                $this->publicationLock->acquire($accountId);
            }

            $this->denyIfAnyHostIsInAnotherPublishedContest($contest, $addedHosts);
            $this->update($contest, $request);
        });

        return new UpdateContestResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateContestRequest;
    }

    /** @param list<ContestHostReference> $hosts */
    private function denyIfAnyHostIsInAnotherPublishedContest(Contest $contest, array $hosts): void
    {
        foreach ($hosts as $host) {
            $published = $this->contestRepository->findPublishedByHost($host);

            if ($published instanceof Contest && !$published->id()->equals($contest->id())) {
                throw new ContestHostAlreadyInUseException($this->hostLocator->name($host));
            }
        }
    }

    private function update(Contest $contest, UpdateContestRequest $request): void
    {
        $locale = LocaleGetter::locale();
        $translating = $locale !== LocaleGetter::default();

        $contest->update(
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
            locale: $translating ? $locale : null,
        );

        $this->contestRepository->add($contest);
    }
}
