<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class ContactRequestRegisteredTemplatesTest extends KernelTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function templates(): array
    {
        return [
            'internal notification' => ['@balance/notification/contact-request-registered.notification.html.twig'],
            'email' => ['@balance/notification/contact-request-registered.email.html.twig'],
        ];
    }

    /**
     * @dataProvider templates
     */
    public function testItEscapesTheExternalData(string $template): void
    {
        self::bootKernel();

        $content = $this->twig()->render($template, [
            'contactRequestId' => (string) ContactRequestId::generate(),
            'contactName' => '<b>Ana</b>',
            'contactEmail' => '<i>ana@example.com</i>',
            'contactPhone' => '<svg onload=alert(1)>',
            'accountName' => '<u>MyCompany</u>',
            'originName' => '<script>alert(1)</script>',
            'co2eqKg' => 150.0,
        ]);

        $this->assertStringNotContainsString('<b>Ana</b>', $content);
        $this->assertStringNotContainsString('<i>', $content);
        $this->assertStringNotContainsString('<u>', $content);
        $this->assertStringNotContainsString('<svg', $content);
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringContainsString('&lt;b&gt;Ana&lt;/b&gt;', $content);
        $this->assertStringContainsString('&lt;u&gt;MyCompany&lt;/u&gt;', $content);
    }

    public function testTheEmailEscapesThePhoneAndTheOrigin(): void
    {
        self::bootKernel();

        $content = $this->twig()->render('@balance/notification/contact-request-registered.email.html.twig', [
            'contactRequestId' => (string) ContactRequestId::generate(),
            'contactName' => 'Ana García',
            'contactEmail' => 'ana@example.com',
            'contactPhone' => '<svg onload=alert(1)>',
            'accountName' => 'MyCompany',
            'originName' => '<script>alert(1)</script>',
            'co2eqKg' => 150.0,
        ]);

        $this->assertStringContainsString('&lt;svg onload=alert(1)&gt;', $content);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $content);
    }

    private function twig(): Environment
    {
        $twig = self::getContainer()->get('twig');

        $this->assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}
