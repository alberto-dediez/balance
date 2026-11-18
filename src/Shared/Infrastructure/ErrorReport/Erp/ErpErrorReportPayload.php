<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport\Erp;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;

class ErpErrorReportPayload
{
    private const int MAX_ENVIRONMENT_LENGTH = 32;

    private const int MAX_CLASS_LENGTH = 255;

    private const int MAX_MESSAGE_LENGTH = 5000;

    private const int MAX_FILE_LENGTH = 500;

    private const int MAX_TRACE_LENGTH = 60000;

    private const int MAX_PREVIOUS_TRACE_LENGTH = 5000;

    private const int MAX_PREVIOUS = 5;

    private const int MAX_NAME_LENGTH = 255;

    private const int MAX_ID_LENGTH = 64;

    private const int MAX_ROLES = 20;

    private const int MAX_ROLE_LENGTH = 64;

    private const int MAX_URI_LENGTH = 2000;

    private const int MAX_METHOD_LENGTH = 10;

    private const int MAX_ROUTE_LENGTH = 255;

    private const int MAX_CLIENT_IP_LENGTH = 64;

    private const int MAX_USER_AGENT_LENGTH = 1000;

    private const int MAX_REFERER_LENGTH = 2000;

    private const int MAX_LOCALE_LENGTH = 16;

    private const int MAX_VALUES = 2000;

    private const int MAX_VALUE_LENGTH = 2000;

    private const int MAX_COMMAND_LENGTH = 500;

    /**
     * @return array<string,mixed>
     */
    public function fromReport(ErrorReport $report, string $projectId): array
    {
        $context = $report->context;
        $request = $report->request;

        return [
            'project_id' => $projectId,
            'fingerprint' => $report->fingerprint,
            'incident_id' => $report->incidentId,
            'status_code' => max(0, $report->statusCode),
            'environment' => $this->text($report->environment, self::MAX_ENVIRONMENT_LENGTH),
            'occurred_at' => $report->occurredAt->format(\DateTimeInterface::ATOM),
            'exception' => $this->exception($report->exception, self::MAX_TRACE_LENGTH),
            'previous' => array_map(
                fn(ErrorReportException $previous): array => $this->exception($previous, self::MAX_PREVIOUS_TRACE_LENGTH),
                array_slice(array_values($report->previous), 0, self::MAX_PREVIOUS),
            ),
            'context' => [
                'username' => $this->nullableText($context->username, self::MAX_NAME_LENGTH),
                'current_account_name' => $this->nullableText($context->currentAccountName, self::MAX_NAME_LENGTH),
                'impersonator_username' => $this->nullableText($context->impersonatorUsername, self::MAX_NAME_LENGTH),
                'user_id' => $this->nullableText($context->userId, self::MAX_ID_LENGTH),
                'roles' => array_map(
                    fn(string $role): string => $this->text($role, self::MAX_ROLE_LENGTH),
                    array_slice(array_values($context->roles), 0, self::MAX_ROLES),
                ),
                'impersonated' => $context->impersonated,
                'impersonator_id' => $this->nullableText($context->impersonatorId, self::MAX_ID_LENGTH),
                'current_account_id' => $this->nullableText($context->currentAccountId, self::MAX_ID_LENGTH),
                'owner_account_id' => $this->nullableText($context->ownerAccountId, self::MAX_ID_LENGTH),
            ],
            'request' => null === $request ? null : [
                'uri' => $this->text($request->uri, self::MAX_URI_LENGTH),
                'method' => $this->text($request->method, self::MAX_METHOD_LENGTH),
                'route' => $this->nullableText($request->route, self::MAX_ROUTE_LENGTH),
                'client_ip' => $this->nullableText($request->clientIp, self::MAX_CLIENT_IP_LENGTH),
                'user_agent' => $this->nullableText($request->userAgent, self::MAX_USER_AGENT_LENGTH),
                'referer' => $this->nullableText($request->referer, self::MAX_REFERER_LENGTH),
                'locale' => $this->nullableText($request->locale, self::MAX_LOCALE_LENGTH),
                'query' => $this->values($request->query),
                'body' => $this->values($request->body),
                'headers' => $this->values($request->headers),
            ],
            'command' => $this->nullableText($report->command, self::MAX_COMMAND_LENGTH),
        ];
    }

    /**
     * @return array{class: string, message: string, file: string, line: int, trace: string}
     */
    private function exception(ErrorReportException $exception, int $maxTraceLength): array
    {
        return [
            'class' => $this->text($exception->class, self::MAX_CLASS_LENGTH),
            'message' => $this->text($exception->message, self::MAX_MESSAGE_LENGTH),
            'file' => $this->text($exception->file, self::MAX_FILE_LENGTH),
            'line' => $exception->line,
            'trace' => $this->text($exception->trace, $maxTraceLength),
        ];
    }

    /**
     * @param array<string,string> $values
     *
     * @return array<string,string>
     */
    private function values(array $values): array
    {
        $result = [];

        foreach (array_slice($values, 0, self::MAX_VALUES, true) as $key => $value) {
            $result[mb_scrub((string) $key, 'UTF-8')] = $this->text($value, self::MAX_VALUE_LENGTH);
        }

        return $result;
    }

    private function text(string $value, int $max): string
    {
        return mb_substr(mb_scrub($value, 'UTF-8'), 0, $max);
    }

    private function nullableText(?string $value, int $max): ?string
    {
        return null === $value ? null : $this->text($value, $max);
    }
}
