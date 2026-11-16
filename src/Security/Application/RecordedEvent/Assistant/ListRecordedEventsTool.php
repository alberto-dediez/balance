<?php

declare(strict_types=1);

namespace App\Security\Application\RecordedEvent\Assistant;

use App\Security\Application\Action;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;
use App\Security\Application\User\UserFinder;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListRecordedEventsTool implements AssistantTool
{
    public function __construct(
        private readonly ListRecordedEventsService $listRecordedEvents,
        private readonly RecordedEventAuthorizer $authorizer,
        private readonly UserFinder $userFinder,
    ) {}

    public function name(): string
    {
        return 'list_recorded_events';
    }

    public function label(): string
    {
        return 'Listar registro de actividad';
    }

    public function description(): string
    {
        return 'Lista los eventos del registro de actividad, del más reciente al más antiguo, por usuario, nombre de evento, contexto, agregado y si los hizo el asistente. No devuelve el contenido de los eventos.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('userId', 'Usuario', 'Id del usuario que hizo la acción.'),
            ToolParameter::string('eventName', 'Evento', 'Nombre del evento, por ejemplo accountRegistered.'),
            ToolParameter::string('context', 'Contexto', 'Contexto del evento, por ejemplo security o changelog.'),
            ToolParameter::string('aggregateRootId', 'Agregado', 'Id del elemento afectado.'),
            ToolParameter::boolean('byAssistant', 'Hecho por el asistente', 'true para los eventos hechos por el asistente, false para los demás.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new RecordedEvent($user->id(), new UserRemoved(UserId::generate())), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $userId = $arguments->string('userId');

        $response = ($this->listRecordedEvents)(
            new ListRecordedEventsRequest(
                userId: null === $userId ? null : new UserId($userId),
                eventName: $arguments->string('eventName'),
                context: $arguments->string('context'),
                aggregateRootId: $arguments->string('aggregateRootId'),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
                byAssistant: $arguments->bool('byAssistant'),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d eventos encontrados', $response->totalResults),
            data: [
                'events' => array_map(
                    fn(RecordedEvent $event): array => [
                        'occurredOn' => $context->dateTime($event->occurredOn()),
                        'eventName' => $event->eventName(),
                        'context' => $event->context(),
                        'aggregateRootId' => $event->aggregateRootId(),
                        'user' => $this->userOf($event),
                        'byAssistant' => $event->isByAssistant(),
                    ],
                    array_values($response->events),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver registro', 'admin_events'),
        );
    }

    /** @return array{id: string, username: ?string}|null */
    private function userOf(RecordedEvent $event): ?array
    {
        $userId = $event->userId();

        if (!$userId instanceof UserId) {
            return null;
        }

        return ['id' => (string) $userId, 'username' => $this->userFinder->find($userId)?->username()];
    }
}
