<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Maintenance;

use App\Shared\Infrastructure\Symfony\Maintenance\MaintenanceGate;
use App\Tests\Unit\Shared\TemporaryDirectory;
use PHPUnit\Framework\TestCase;

class MaintenanceGateTest extends TestCase
{
    use TemporaryDirectory;

    private const string PAGE = '<html><body>Mantenimiento de prueba</body></html>';

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = rtrim($this->temporaryDirectoryPath('maintenance-gate'), '/');
        mkdir($this->projectDir . '/var', 0o777, true);
        mkdir($this->projectDir . '/templates', 0o777, true);
        file_put_contents($this->projectDir . '/' . MaintenanceGate::PAGE_FILE, self::PAGE);
    }

    protected function tearDown(): void
    {
        $this->removeTemporaryDirectory($this->projectDir);
    }

    public function testItReturnsNullWithoutFlagFile(): void
    {
        $this->assertNull(MaintenanceGate::responseFor($this->projectDir, '/es/admin'));
    }

    public function testItServesTheMaintenancePageForWebPaths(): void
    {
        $this->enableMaintenance();

        $response = MaintenanceGate::responseFor($this->projectDir, '/es/admin/users');

        $this->assertNotNull($response);
        $this->assertSame(503, $response['status']);
        $this->assertSame('text/html; charset=UTF-8', $response['headers']['Content-Type']);
        $this->assertSame(self::PAGE, $response['body']);
    }

    public function testItServesJsonForApiPaths(): void
    {
        $this->enableMaintenance();

        $response = MaintenanceGate::responseFor($this->projectDir, '/api/development/agent-runs/x/output');

        $this->assertNotNull($response);
        $this->assertSame(503, $response['status']);
        $this->assertSame('application/json', $response['headers']['Content-Type']);
        $this->assertSame('{"status":"maintenance"}', $response['body']);
    }

    /**
     * @dataProvider healthCheckPaths
     */
    public function testItBlocksHealthChecks(string $path): void
    {
        $this->enableMaintenance();

        $response = MaintenanceGate::responseFor($this->projectDir, $path);

        $this->assertNotNull($response);
        $this->assertSame(503, $response['status']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function healthCheckPaths(): array
    {
        return [
            'web' => ['/health-check'],
            'api' => ['/api/health-check'],
        ];
    }

    public function testItLetsDeploymentWebhooksThrough(): void
    {
        $this->enableMaintenance();

        $this->assertNull(MaintenanceGate::responseFor($this->projectDir, '/webhooks/deployments/abc'));
    }

    public function testItBlocksDeploymentWebhookPathsOutsideTheRoot(): void
    {
        $this->enableMaintenance();

        $response = MaintenanceGate::responseFor($this->projectDir, '/es/x/webhooks/deployments');

        $this->assertNotNull($response);
        $this->assertSame(503, $response['status']);
    }

    /**
     * @dataProvider commonHeaderPaths
     */
    public function testItSendsRetryAfterAndNoCacheHeaders(string $path): void
    {
        $this->enableMaintenance();

        $response = MaintenanceGate::responseFor($this->projectDir, $path);

        $this->assertNotNull($response);
        $this->assertSame('30', $response['headers']['Retry-After']);
        $this->assertSame('no-store, no-cache, must-revalidate', $response['headers']['Cache-Control']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commonHeaderPaths(): array
    {
        return [
            'web' => ['/es/admin'],
            'api' => ['/api/health-check'],
        ];
    }

    public function testItFallsBackToPlainTextWhenThePageIsMissing(): void
    {
        $this->enableMaintenance();
        unlink($this->projectDir . '/' . MaintenanceGate::PAGE_FILE);

        $response = MaintenanceGate::responseFor($this->projectDir, '/es/admin');

        $this->assertNotNull($response);
        $this->assertStringContainsString('Estamos en mantenimiento', $response['body']);
    }

    public function testTheRealMaintenancePageExists(): void
    {
        $this->assertFileExists(\dirname(__DIR__, 6) . '/' . MaintenanceGate::PAGE_FILE);
    }

    private function enableMaintenance(): void
    {
        touch($this->projectDir . '/' . MaintenanceGate::FLAG_FILE);
    }
}
