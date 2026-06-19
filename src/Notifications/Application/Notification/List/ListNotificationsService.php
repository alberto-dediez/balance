<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\List;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListNotificationsService
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly NotificationAuthorizer $authorizer,
    ) {}

    public function __invoke(ListNotificationsRequest $request, User $user): ListNotificationsResponse
    {
        $isOwnListing = null !== $request->recipientId && $request->recipientId->equals($user->id());

        if (!$isOwnListing) {
            $this->authorizer->denyUnlessCanDoThat(new Notification(NotificationId::generate()), SecurityAction::List, $user);
        }

        $notifications = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListNotificationsResponse($notifications, $totalResults);
    }
}
