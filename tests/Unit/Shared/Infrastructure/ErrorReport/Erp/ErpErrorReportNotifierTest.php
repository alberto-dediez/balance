<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport\Erp;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;
use App\Shared\Infrastructure\ErrorReport\Erp\ErpErrorReportClient;
use App\Shared\Infrastructure\ErrorReport\Erp\ErpErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\Erp\ErpErrorReportPayload;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\StubErrorReportThrottle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ErpErrorReportNotifierTest extends TestCase
{
    private const string PROJECT_ID = '0b6a9f1e-1d2c-4b3a-9e8f-7a6b5c4d3e2f';

    private int $requests = 0;

    /** @var list<string> */
    private array $bodies = [];

    private SpyLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new SpyLogger();
    }

    public function testItReportsTheErrorToTheErp(): void
    {
        $sut = $this->createSUT([$this->jwtResponse(), new MockResponse('{}', ['http_code' => 201])], projectId: ' ' . self::PROJECT_ID . ' ');

        $sut($this->report());

        $this->assertSame(2, $this->requests);

        $body = json_decode($this->bodies[1], true);

        $this->assertIsArray($body);
        $this->assertSame(self::PROJECT_ID, $body['project_id']);
        $this->assertSame('ABCD1234', $body['incident_id']);
        $this->assertSame([], $this->logger->levels());
    }

    public function testItDoesNotCallTheErpWhenItIsDisabled(): void
    {
        $sut = $this->createSUT([], enabled: false);

        $sut($this->report());

        $this->assertSame(0, $this->requests);
        $this->assertSame([], $this->logger->levels());
    }

    public function testItDoesNotCallTheErpWithoutProject(): void
    {
        $sut = $this->createSUT([], projectId: ' ');

        $sut($this->report());

        $this->assertSame(0, $this->requests);
        $this->assertSame(['warning'], $this->logger->levels());
    }

    public function testItDoesNotCallTheErpWhenTheClientIsNotConfigured(): void
    {
        $sut = $this->createSUT([], url: '');

        $sut($this->report());

        $this->assertSame(0, $this->requests);
        $this->assertSame(['warning'], $this->logger->levels());
    }

    public function testItDoesNotCallTheErpWhenTheErrorIsThrottled(): void
    {
        $sut = $this->createSUT([], throttle: new StubErrorReportThrottle(false));

        $sut($this->report());

        $this->assertSame(0, $this->requests);
        $this->assertSame([], $this->logger->levels());
    }

    public function testItDoesNotPropagateErpErrors(): void
    {
        $sut = $this->createSUT([$this->jwtResponse(), new MockResponse('{}', ['http_code' => 500])]);

        $sut($this->report());

        $this->assertSame(2, $this->requests);
        $this->assertSame(['error'], $this->logger->levels());
    }

    public function testItDoesNotPropagateTransportErrors(): void
    {
        $sut = $this->createSUT([new MockResponse('', ['error' => 'Could not resolve host'])]);

        $sut($this->report());

        $this->assertSame(1, $this->requests);
        $this->assertSame(['error'], $this->logger->levels());
    }

    private function report(): ErrorReport
    {
        return new ErrorReport(
            incidentId: 'ABCD1234',
            fingerprint: 'a-fingerprint',
            statusCode: 500,
            exception: new ErrorReportException(\RuntimeException::class, 'boom', '/app/src/Foo.php', 12),
            context: new ErrorReportContext(),
            environment: 'prod',
        );
    }

    private function jwtResponse(): MockResponse
    {
        return new MockResponse('{"token":"a-jwt"}', ['http_code' => 200]);
    }

    /**
     * @param MockResponse[] $responses
     */
    private function createSUT(
        array $responses,
        ?ErrorReportThrottle $throttle = null,
        bool $enabled = true,
        string $projectId = self::PROJECT_ID,
        string $url = 'https://erp.test',
    ): ErpErrorReportNotifier {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            ++$this->requests;
            $this->bodies[] = is_string($options['body'] ?? null) ? $options['body'] : '';

            $response = array_shift($responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        return new ErpErrorReportNotifier(
            client: new ErpErrorReportClient(
                httpClient: $httpClient,
                cache: new ArrayAdapter(),
                url: $url,
                username: 'platform@email.com',
                apiToken: 'a-secret-api-token',
            ),
            payload: new ErpErrorReportPayload(),
            throttle: $throttle ?? new StubErrorReportThrottle(),
            logger: $this->logger,
            enabled: $enabled,
            projectId: $projectId,
        );
    }
}
