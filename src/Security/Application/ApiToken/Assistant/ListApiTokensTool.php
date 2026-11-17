<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Assistant;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\List\ListApiTokensRequest;
use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenStatus;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListApiTokensTool implements AssistantTool
{
    public function __construct(
        private readonly ListApiTokensService $listApiTokens,
        private readonly UserAuthorizer $userAuthorizer,
    ) {}

    public function name(): string
    {
        return 'list_api_tokens';
    }

    public function label(): string
    {
        return 'Listar tokens de API';
    }

    public function description(): string
    {
        return 'Lista los tokens de API de un usuario por estado. Devuelve su nombre, estado y fechas; nunca el token. Necesita el id del usuario (búscalo antes con list_users).';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('userId', 'Usuario', 'Id del usuario dueño de los tokens.')->required(),
            ToolParameter::enum('status', 'Estado', 'Estado del token.', array_column(ApiTokenStatus::cases(), 'value')),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->userAuthorizer->canDoThat(new User(UserId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $status = $arguments->string('status');
        $userId = (string) $arguments->string('userId');

        $response = ($this->listApiTokens)(
            new ListApiTokensRequest(
                userId: new UserId($userId),
                status: null === $status ? null : ApiTokenStatus::from($status),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d tokens de API encontrados', $response->totalResults),
            data: [
                'apiTokens' => array_map(
                    static fn(ApiToken $apiToken): array => [
                        'name' => $apiToken->name(),
                        'status' => $apiToken->currentStatus()->value,
                        'registeredAt' => $context->dateTime($apiToken->registeredAt()),
                        'expiresAt' => $context->dateTime($apiToken->expiresAt()),
                        'lastUsedAt' => $context->dateTime($apiToken->lastUsedAt()),
                        'revokedAt' => $context->dateTime($apiToken->revokedAt()),
                    ],
                    array_values($response->apiTokens),
                ),
                'total' => $response->totalResults,
            ],
            references: ['userId' => $userId],
            link: new ToolLink('Ver tokens', 'admin_users_api_tokens', ['userId']),
        );
    }
}
