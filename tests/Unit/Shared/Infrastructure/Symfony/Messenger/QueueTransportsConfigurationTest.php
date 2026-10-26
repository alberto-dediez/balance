<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class QueueTransportsConfigurationTest extends TestCase
{
    public function testHealthCheckQueueTransportsMatchMessengerConfiguration(): void
    {
        $projectDir = \dirname(__DIR__, 6);

        $messenger = Yaml::parseFile($projectDir . '/config/packages/messenger.yaml');
        $services = Yaml::parseFile($projectDir . '/src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml');

        $expected = [];

        foreach ($messenger['framework']['messenger']['transports'] as $name => $transport) {
            if (\is_array($transport) && isset($transport['options']['queue_name'])) {
                $expected[$name] = $transport['options']['queue_name'];
            }
        }

        self::assertNotEmpty($expected);
        self::assertSame($expected, $services['parameters']['shared.health_check.queue_transports']);
    }
}
