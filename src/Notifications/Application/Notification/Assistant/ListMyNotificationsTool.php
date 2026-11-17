<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Assistant;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListMyNotificationsTool implements AssistantTool
{
    public function __construct(
        private readonly ListNotificationsService $listNotifications,
        private readonly NotificationAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_my_notifications';
    }

    public function label(): string
    {
        return 'Listar mis notificaciones';
    }

    public function description(): string
    {
        return 'Lista las notificaciones del usuario que conversa, de la más reciente a la más antigua, con su título, fecha y si están leídas.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::boolean('onlyUnread', 'Solo sin leer', 'true para ver solo las notificaciones sin leer.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Notification(NotificationId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $response = ($this->listNotifications)(
            new ListNotificationsRequest(
                recipientId: $user->id(),
                onlyUnread: $arguments->bool('onlyUnread'),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d notificaciones encontradas', $response->totalResults),
            data: [
                'notifications' => array_map(
                    static fn(Notification $notification): array => [
                        'subject' => $notification->subject(),
                        'createdAt' => $context->dateTime($notification->createdAt()),
                        'read' => $notification->isRead(),
                    ],
                    array_values($response->notifications),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver notificaciones', 'notifications'),
        );
    }
}
