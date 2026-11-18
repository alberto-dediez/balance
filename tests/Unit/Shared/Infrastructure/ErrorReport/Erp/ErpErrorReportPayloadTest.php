<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport\Erp;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportRequest;
use App\Shared\Infrastructure\ErrorReport\Erp\ErpErrorReportPayload;
use PHPUnit\Framework\TestCase;

class ErpErrorReportPayloadTest extends TestCase
{
    private const string PROJECT_ID = '0b6a9f1e-1d2c-4b3a-9e8f-7a6b5c4d3e2f';

    public function testItBuildsThePayloadOfAnHttpReport(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->httpReport(), self::PROJECT_ID);

        $this->assertSame([
            'project_id' => self::PROJECT_ID,
            'fingerprint' => 'a-fingerprint',
            'incident_id' => 'ABCD1234',
            'status_code' => 500,
            'environment' => 'prod',
            'occurred_at' => '2026-11-16T10:30:00+00:00',
            'exception' => [
                'class' => \RuntimeException::class,
                'message' => 'boom',
                'file' => '/app/src/Foo.php',
                'line' => 12,
                'trace' => '#0 /app/src/Foo.php(12): foo()',
            ],
            'previous' => [
                [
                    'class' => \LogicException::class,
                    'message' => 'inner',
                    'file' => '/app/src/Bar.php',
                    'line' => 7,
                    'trace' => '#0 /app/src/Bar.php(7): bar()',
                ],
            ],
            'context' => [
                'username' => 'admin@email.com',
                'current_account_name' => 'impersonada',
                'impersonator_username' => 'super@email.com',
                'user_id' => 'a-user-id',
                'roles' => ['ROLE_OWNER', 'ROLE_USER'],
                'impersonated' => true,
                'impersonator_id' => 'an-impersonator-id',
                'current_account_id' => 'an-account-id',
                'owner_account_id' => 'another-account-id',
            ],
            'request' => [
                'uri' => 'https://localhost/es/waste?page=2',
                'method' => 'POST',
                'route' => 'waste_list',
                'client_ip' => '127.0.0.1',
                'user_agent' => 'a browser',
                'referer' => 'https://localhost/es',
                'locale' => 'es',
                'query' => ['page' => '2'],
                'body' => ['username' => 'admin@email.com', 'password' => '***'],
                'headers' => ['user-agent' => 'a browser'],
            ],
            'command' => null,
        ], $payload);
    }

    public function testItKeepsRedactedValues(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->httpReport(), self::PROJECT_ID);

        $this->assertIsArray($payload['request']);
        $this->assertIsArray($payload['request']['body']);
        $this->assertSame('***', $payload['request']['body']['password']);
    }

    public function testItLimitsTheNumberOfValuesRolesAndThePreviousTrace(): void
    {
        $query = [];

        for ($i = 0; $i < 2100; ++$i) {
            $query[sprintf('key%d', $i)] = 'value';
        }

        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(
            context: new ErrorReportContext(roles: array_fill(0, 25, 'ROLE_USER')),
            request: new ErrorReportRequest(uri: 'https://localhost', method: 'GET', query: $query),
            previous: [new ErrorReportException(\LogicException::class, 'inner', '/app/src/Bar.php', 7, str_repeat('a', 6000))],
        ), self::PROJECT_ID);

        $this->assertIsArray($payload['request']);
        $this->assertIsArray($payload['request']['query']);
        $this->assertCount(2000, $payload['request']['query']);
        $this->assertIsArray($payload['context']);
        $this->assertIsArray($payload['context']['roles']);
        $this->assertCount(20, $payload['context']['roles']);
        $this->assertIsArray($payload['previous']);
        $this->assertIsArray($payload['previous'][0]);
        $this->assertIsString($payload['previous'][0]['trace']);
        $this->assertSame(5000, mb_strlen($payload['previous'][0]['trace']));
    }

    public function testItSendsEmptyMapsAsEmptyArrays(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(
            request: new ErrorReportRequest(uri: 'https://localhost', method: 'GET'),
        ), self::PROJECT_ID);

        $this->assertIsArray($payload['request']);
        $this->assertSame([], $payload['request']['query']);
        $this->assertSame([], $payload['request']['body']);
        $this->assertSame([], $payload['request']['headers']);
    }

    public function testItBuildsThePayloadOfAConsoleReport(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(statusCode: 1, command: 'app:do-something'), self::PROJECT_ID);

        $this->assertNull($payload['request']);
        $this->assertSame('app:do-something', $payload['command']);
        $this->assertSame(1, $payload['status_code']);
    }

    public function testItBuildsTheContextOfAnAnonymousUser(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(), self::PROJECT_ID);

        $this->assertSame([
            'username' => null,
            'current_account_name' => null,
            'impersonator_username' => null,
            'user_id' => null,
            'roles' => [],
            'impersonated' => false,
            'impersonator_id' => null,
            'current_account_id' => null,
            'owner_account_id' => null,
        ], $payload['context']);
    }

    public function testItTruncatesTheMessage(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(
            exception: new ErrorReportException(\RuntimeException::class, str_repeat('a', 6000), '/app/src/Foo.php', 12),
        ), self::PROJECT_ID);

        $this->assertIsArray($payload['exception']);
        $this->assertIsString($payload['exception']['message']);
        $this->assertSame(5000, mb_strlen($payload['exception']['message']));
    }

    public function testItProducesValidUtf8FromATraceWithABrokenMultibyteCharacter(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(
            exception: new ErrorReportException(\RuntimeException::class, 'boom', '/app/src/Foo.php', 12, 'trace ñ' . substr('ñ', 0, 1)),
        ), self::PROJECT_ID);

        $this->assertIsArray($payload['exception']);
        $this->assertIsString($payload['exception']['trace']);
        $this->assertTrue(mb_check_encoding($payload['exception']['trace'], 'UTF-8'));
        $this->assertJson(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function testItNeverSendsANegativeStatusCode(): void
    {
        $sut = $this->createSUT();

        $payload = $sut->fromReport($this->report(statusCode: -1), self::PROJECT_ID);

        $this->assertSame(0, $payload['status_code']);
    }

    private function httpReport(): ErrorReport
    {
        return $this->report(
            context: new ErrorReportContext(
                userId: 'a-user-id',
                username: 'admin@email.com',
                roles: ['ROLE_OWNER', 'ROLE_USER'],
                impersonated: true,
                impersonatorId: 'an-impersonator-id',
                impersonatorUsername: 'super@email.com',
                currentAccountId: 'an-account-id',
                currentAccountName: 'impersonada',
                ownerAccountId: 'another-account-id',
            ),
            request: new ErrorReportRequest(
                uri: 'https://localhost/es/waste?page=2',
                method: 'POST',
                route: 'waste_list',
                clientIp: '127.0.0.1',
                userAgent: 'a browser',
                referer: 'https://localhost/es',
                locale: 'es',
                query: ['page' => '2'],
                body: ['username' => 'admin@email.com', 'password' => '***'],
                headers: ['user-agent' => 'a browser'],
            ),
            previous: [new ErrorReportException(\LogicException::class, 'inner', '/app/src/Bar.php', 7, '#0 /app/src/Bar.php(7): bar()')],
        );
    }

    /**
     * @param ErrorReportException[] $previous
     */
    private function report(
        int $statusCode = 500,
        ?ErrorReportException $exception = null,
        ?ErrorReportContext $context = null,
        ?ErrorReportRequest $request = null,
        ?string $command = null,
        array $previous = [],
    ): ErrorReport {
        return new ErrorReport(
            incidentId: 'ABCD1234',
            fingerprint: 'a-fingerprint',
            statusCode: $statusCode,
            exception: $exception ?? new ErrorReportException(
                class: \RuntimeException::class,
                message: 'boom',
                file: '/app/src/Foo.php',
                line: 12,
                trace: '#0 /app/src/Foo.php(12): foo()',
            ),
            context: $context ?? new ErrorReportContext(),
            request: $request,
            command: $command,
            previous: $previous,
            environment: 'prod',
            occurredAt: new \DateTimeImmutable('2026-11-16T10:30:00+00:00'),
        );
    }

    private function createSUT(): ErpErrorReportPayload
    {
        return new ErpErrorReportPayload();
    }
}
