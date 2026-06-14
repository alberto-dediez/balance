<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Send;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\AudienceType;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SendNotificationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationAuthorizer $authorizer,
    ) {}

    public function __invoke(SendNotificationRequest $request, User $user): SendNotificationResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Notification(NotificationId::generate()), SecurityAction::Register, $user);

        $recipients = $this->resolveRecipients($request);

        foreach ($recipients as $recipient) {
            foreach ($request->channels as $channel) {
                CommandBusDispatcher::instance()->dispatch(
                    new DeferredCommand(
                        command: new DeliverNotificationRequest(
                            recipientId: (string) $recipient->id(),
                            channel: $channel,
                            subject: $request->subject,
                            body: $request->body,
                            accountId: null !== $recipient->accountId() ? (string) $recipient->accountId() : null,
                            sentBy: (string) $user->id(),
                        ),
                        priority: Priority::LOW,
                    ),
                    $user,
                );
            }
        }

        return new SendNotificationResponse(recipientCount: count($recipients));
    }

    /**
     * @return list<User>
     */
    private function resolveRecipients(SendNotificationRequest $request): array
    {
        return match ($request->audienceType) {
            AudienceType::User => $this->resolveSingleUser($request->targetUserId),
            AudienceType::Account => null !== $request->targetAccountId
                ? $this->userRepository->findByAccountId(AccountId::fromString($request->targetAccountId))
                : [],
            AudienceType::AccountRole => $this->resolveAccountRole($request),
            AudienceType::AllPlatform => $this->userRepository->findByFilter(new UserFilter(limit: 0)),
        };
    }

    /**
     * @return list<User>
     */
    private function resolveSingleUser(?string $userId): array
    {
        if (null === $userId) {
            return [];
        }

        $user = $this->userRepository->find(UserId::fromString($userId));

        return $user instanceof User ? [$user] : [];
    }

    /**
     * @return list<User>
     */
    private function resolveAccountRole(SendNotificationRequest $request): array
    {
        if (null === $request->targetAccountId || null === $request->targetRole) {
            return [];
        }

        $users = $this->userRepository->findByAccountId(AccountId::fromString($request->targetAccountId));

        return array_values(array_filter($users, fn(User $user): bool => $user->hasRole($request->targetRole)));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SendNotificationRequest;
    }
}
