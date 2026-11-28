<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Publish;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Transaction\TransactionalSession;

class PublishContestService implements CommandHandler
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

    public function __invoke(PublishContestRequest $request, User $user): PublishContestResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Edit, $user);

        if (!$request->responsibilityAccepted) {
            throw new \InvalidArgumentException('Debes aceptar la declaración de responsabilidad para publicar el concurso.');
        }

        $this->transactionalSession->run(function () use ($contest, $user): void {
            $accountId = $contest->accountId();

            if (null !== $accountId) {
                $this->publicationLock->acquire($accountId);
            }

            foreach ($contest->hostReferences() as $host) {
                $published = $this->contestRepository->findPublishedByHost($host);

                if ($published instanceof Contest && !$published->id()->equals($contest->id())) {
                    throw new ContestHostAlreadyInUseException($this->hostLocator->name($host));
                }
            }

            $contest->publish($user->id(), new \DateTimeImmutable('NOW'));
            $this->contestRepository->add($contest);
        });

        return new PublishContestResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PublishContestRequest;
    }
}
