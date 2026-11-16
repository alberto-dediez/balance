<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Assistant;

use App\Security\Application\Action;
use App\Security\Application\SignatureRequest\List\ListSignatureRequestsRequest;
use App\Security\Application\SignatureRequest\List\ListSignatureRequestsService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListSignatureRequestsTool implements AssistantTool
{
    public function __construct(
        private readonly ListSignatureRequestsService $listSignatureRequests,
        private readonly SignatureRequestAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_signature_requests';
    }

    public function label(): string
    {
        return 'Listar solicitudes de firma';
    }

    public function description(): string
    {
        return 'Lista las solicitudes de firma electrónica por cuenta y estado.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('accountId', 'Cuenta', 'Id de la cuenta de las solicitudes.'),
            ToolParameter::enum('status', 'Estado', 'Estado de la solicitud.', array_column(SignatureRequestStatus::cases(), 'value')),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new SignatureRequest(id: SignatureRequestId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $accountId = $arguments->string('accountId');
        $status = $arguments->string('status');

        $response = ($this->listSignatureRequests)(
            new ListSignatureRequestsRequest(
                accountId: null === $accountId ? null : new AccountId($accountId),
                signatureStatus: null === $status ? null : SignatureRequestStatus::from($status),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d solicitudes de firma encontradas', $response->totalResults),
            data: [
                'signatureRequests' => array_map(
                    static fn(SignatureRequest $signatureRequest): array => [
                        'id' => (string) $signatureRequest->id(),
                        'accountId' => null === $signatureRequest->accountId() ? null : (string) $signatureRequest->accountId(),
                        'status' => $signatureRequest->status()->value,
                        'registeredAt' => $context->dateTime($signatureRequest->registeredAt()),
                        'updatedAt' => $context->dateTime($signatureRequest->updatedAt()),
                        'expiresAt' => $context->dateTime($signatureRequest->expiresAt()),
                    ],
                    array_values($response->signatureRequests),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver solicitudes de firma', 'admin_signature_requests'),
        );
    }
}
