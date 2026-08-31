<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportRequest;
use App\Shared\Domain\Uuid\UuidGenerator;
use Symfony\Component\HttpFoundation\Request;

class ErrorReportFactory
{
    private const int MAX_TRACE_LENGTH = 60000;

    private const int MAX_VALUE_LENGTH = 500;

    private const int MAX_PREVIOUS = 5;

    private const string REDACTED = '***';

    /** @var string[] */
    private const array SENSITIVE_KEYS = [
        'password',
        'passwd',
        'token',
        'secret',
        'authorization',
        'api_key',
        'apikey',
        'jwt',
        'cookie',
        'hmac',
        'signature',
        '_csrf',
        'credentials',
    ];

    /** @var string[] */
    private const array ALLOWED_HEADERS = [
        'host',
        'user-agent',
        'referer',
        'accept',
        'accept-language',
        'content-type',
        'x-requested-with',
        'x-forwarded-for',
    ];

    public function __construct(
        private readonly LoggedUser $loggedUser,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly CurrentRolesProvider $currentRolesProvider,
        private readonly AccountRepository $accountRepository,
        private readonly string $environment,
    ) {}

    public function fromRequest(\Throwable $error, int $statusCode, ?Request $request): ErrorReport
    {
        return new ErrorReport(
            incidentId: $this->incidentId(),
            fingerprint: $this->fingerprint($error, $statusCode),
            statusCode: $statusCode,
            exception: $this->exception($error),
            context: $this->context(),
            request: $request instanceof Request ? $this->request($request) : null,
            previous: $this->previous($error),
            environment: $this->environment,
        );
    }

    public function fromCommand(\Throwable $error, int $exitCode, ?string $command): ErrorReport
    {
        return new ErrorReport(
            incidentId: $this->incidentId(),
            fingerprint: $this->fingerprint($error, $exitCode),
            statusCode: $exitCode,
            exception: $this->exception($error),
            context: $this->context(),
            command: $command,
            previous: $this->previous($error),
            environment: $this->environment,
        );
    }

    private function incidentId(): string
    {
        return strtoupper(substr(str_replace('-', '', UuidGenerator::v4()), 0, 8));
    }

    private function fingerprint(\Throwable $error, int $statusCode): string
    {
        return sha1(sprintf('%s|%s|%d|%d', $error::class, $error->getFile(), $error->getLine(), $statusCode));
    }

    private function exception(\Throwable $error): ErrorReportException
    {
        return new ErrorReportException(
            class: $error::class,
            message: $error->getMessage(),
            file: $error->getFile(),
            line: $error->getLine(),
            trace: substr($error->getTraceAsString(), 0, self::MAX_TRACE_LENGTH),
        );
    }

    /**
     * @return ErrorReportException[]
     */
    private function previous(\Throwable $error): array
    {
        $result = [];
        $previous = $error->getPrevious();

        while ($previous instanceof \Throwable && count($result) < self::MAX_PREVIOUS) {
            $result[] = $this->exception($previous);

            $previous = $previous->getPrevious();
        }

        return $result;
    }

    private function context(): ErrorReportContext
    {
        $user = $this->loggedUser($this->loggedUser->getLoggedUserOrNull(...));

        if (!$user instanceof User) {
            return new ErrorReportContext();
        }

        $impersonator = $this->loggedUser($this->loggedUser->getImpersonatorOrNull(...));
        $currentAccountId = $this->currentAccountId();
        $ownerAccountId = $user->accountId();

        return new ErrorReportContext(
            userId: $user->id()->id(),
            username: $user->username(),
            roles: $this->roles($user),
            impersonated: $impersonator instanceof User
                || ($currentAccountId instanceof AccountId && !$currentAccountId->equals($ownerAccountId ?? $currentAccountId)),
            impersonatorId: $impersonator instanceof User ? $impersonator->id()->id() : null,
            impersonatorUsername: $impersonator instanceof User ? $impersonator->username() : null,
            currentAccountId: $currentAccountId?->id(),
            currentAccountName: $this->accountName($currentAccountId),
            ownerAccountId: $ownerAccountId?->id(),
        );
    }

    /**
     * @param callable():?User $resolver
     */
    private function loggedUser(callable $resolver): ?User
    {
        try {
            return $resolver();
        } catch (\Throwable) {
            return null;
        }
    }

    private function currentAccountId(): ?AccountId
    {
        try {
            return $this->currentAccountIdProvider->currentAccountId();
        } catch (\Throwable) {
            return null;
        }
    }

    private function accountName(?AccountId $accountId): ?string
    {
        if (!$accountId instanceof AccountId) {
            return null;
        }

        try {
            $account = $this->accountRepository->find($accountId);
        } catch (\Throwable) {
            return null;
        }

        return $account instanceof Account ? $account->name() : null;
    }

    /**
     * @return string[]
     */
    private function roles(User $user): array
    {
        try {
            $roles = $this->currentRolesProvider->currentRoles($user) ?? $user->roles();
        } catch (\Throwable) {
            $roles = $user->roles();
        }

        return array_map(static fn(Role $role): string => $role->value, $roles);
    }

    private function request(Request $request): ErrorReportRequest
    {
        $route = $request->attributes->get('_route');

        return new ErrorReportRequest(
            uri: $request->getUri(),
            method: $request->getMethod(),
            route: is_string($route) ? $route : null,
            clientIp: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            referer: $request->headers->get('Referer'),
            locale: $request->getLocale(),
            query: $this->sanitize($request->query->all()),
            body: $this->sanitize($request->request->all()),
            headers: $this->headers($request),
        );
    }

    /**
     * @return array<string,string>
     */
    private function headers(Request $request): array
    {
        $result = [];

        foreach ($request->headers->all() as $name => $values) {
            if (!in_array(strtolower($name), self::ALLOWED_HEADERS, true)) {
                continue;
            }

            $result[$name] = $this->stringify($values);
        }

        return $result;
    }

    /**
     * @param array<array-key,mixed> $values
     *
     * @return array<string,string>
     */
    private function sanitize(array $values, string $prefix = ''): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $name = '' === $prefix ? (string) $key : sprintf('%s[%s]', $prefix, $key);

            if (is_array($value)) {
                $result = array_merge($result, $this->sanitize($value, $name));

                continue;
            }

            $result[$name] = $this->isSensitive((string) $key) ? self::REDACTED : $this->stringify($value);
        }

        return $result;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function stringify(mixed $value): string
    {
        $result = match (true) {
            is_scalar($value) => (string) $value,
            null === $value => '',
            $value instanceof \Stringable => (string) $value,
            default => (string) json_encode($value),
        };

        return substr($result, 0, self::MAX_VALUE_LENGTH);
    }
}
