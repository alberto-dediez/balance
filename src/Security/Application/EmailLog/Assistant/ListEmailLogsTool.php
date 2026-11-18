<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Assistant;

use App\Security\Application\Action;
use App\Security\Application\EmailLog\EmailLogAuthorizer;
use App\Security\Application\EmailLog\List\ListEmailLogsRequest;
use App\Security\Application\EmailLog\List\ListEmailLogsService;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListEmailLogsTool implements AssistantTool
{
    public function __construct(
        private readonly ListEmailLogsService $listEmailLogs,
        private readonly EmailLogAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_email_logs';
    }

    public function label(): string
    {
        return 'Listar emails enviados';
    }

    public function description(): string
    {
        return 'Lista los emails enviados por la plataforma, del más reciente al más antiguo, por destinatario, asunto, estado y fecha de envío. No devuelve el cuerpo de los emails.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('recipient', 'Destinatario', 'Parte del email o del nombre del destinatario.'),
            ToolParameter::string('subject', 'Asunto', 'Parte del asunto.'),
            ToolParameter::enum('status', 'Estado', 'Estado del envío.', array_column(EmailLogStatus::cases(), 'value')),
            ToolParameter::date('sentFrom', 'Enviado desde', 'Primer día de envío.'),
            ToolParameter::date('sentUntil', 'Enviado hasta', 'Último día de envío, incluido.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new EmailLog(EmailLogId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $status = $arguments->string('status');

        $response = ($this->listEmailLogs)(
            new ListEmailLogsRequest(
                recipient: $arguments->string('recipient'),
                subject: $arguments->string('subject'),
                status: null === $status ? null : EmailLogStatus::from($status),
                sentFrom: $arguments->date('sentFrom'),
                sentUntil: $arguments->date('sentUntil')?->setTime(23, 59, 59),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d emails encontrados', $response->totalResults),
            data: [
                'emails' => array_map(
                    static fn(EmailLog $emailLog): array => [
                        'href' => ToolLink::href('admin_emails_show', ['emailLogId' => (string) $emailLog->id()]),
                        'recipient' => $emailLog->recipientEmail(),
                        'subject' => $emailLog->subject(),
                        'status' => $emailLog->status()->value,
                        'sentAt' => $context->dateTime($emailLog->sentAt()),
                        'template' => $emailLog->template(),
                    ],
                    array_values($response->emailLogs),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver emails', 'admin_emails'),
        );
    }
}
