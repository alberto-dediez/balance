<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Infrastructure\Symfony\Twig;

use App\Assistant\Infrastructure\Symfony\Twig\AssistantLinkResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AssistantLinkResolverTest extends TestCase
{
    public const string ACCOUNT_ID = '0190f3a4-1b2c-7d3e-8f40-000000000001';

    public static function createSUT(): AssistantLinkResolver
    {
        $routes = new RouteCollection();
        $routes->add('admin_users', new Route('/{_locale}/admin/users'));
        $routes->add('admin_accounts_edit', new Route('/{_locale}/admin/accounts/edit/{accountId}', requirements: ['accountId' => '[0-9a-f-]{36}']));
        $routes->add('_profiler', new Route('/_profiler'));

        $context = new RequestContext(host: 'hydra.test', scheme: 'https');
        $context->setParameter('_locale', 'es');

        return new AssistantLinkResolver(new UrlGenerator($routes, $context));
    }

    public function testRecognisesReferences(): void
    {
        $resolver = self::createSUT();

        $this->assertTrue($resolver->isReference('route:admin_users'));
        $this->assertFalse($resolver->isReference('/es/admin/users'));
    }

    public function testResolvesWithParameters(): void
    {
        $this->assertSame(
            '/es/admin/accounts/edit/' . self::ACCOUNT_ID,
            self::createSUT()->resolve('route:admin_accounts_edit?accountId=' . self::ACCOUNT_ID),
        );
    }

    public function testAnUnknownRouteIsNotResolved(): void
    {
        $this->assertNull(self::createSUT()->resolve('route:no_existe'));
    }

    public function testAMissingMandatoryParameterIsNotResolved(): void
    {
        $this->assertNull(self::createSUT()->resolve('route:admin_accounts_edit'));
    }

    public function testAParameterThatBreaksTheRequirementsIsNotResolved(): void
    {
        $this->assertNull(self::createSUT()->resolve('route:admin_accounts_edit?accountId=x'));
    }

    public function testAnExtraParameterIsNotAddedAsQueryString(): void
    {
        $this->assertSame('/es/admin/users', self::createSUT()->resolve('route:admin_users?_token=abc&page=2'));
    }

    public function testAnInternalRouteIsNotResolved(): void
    {
        $this->assertNull(self::createSUT()->resolve('route:_profiler'));
    }

    public function testAnArrayValueIsNotResolved(): void
    {
        $this->assertNull(self::createSUT()->resolve('route:admin_users?a[]=1'));
    }

    public function testResolvesAnAbsoluteUrl(): void
    {
        $this->assertSame('https://hydra.test/es/admin/users', self::createSUT()->resolve('route:admin_users', true));
    }

    public function testAnUrlOfTheOwnHostIsInternal(): void
    {
        $this->assertTrue(self::createSUT()->isInternal('https://HYDRA.test/es/admin/users'));
    }

    public function testAnUrlOfAnotherHostIsNotInternal(): void
    {
        $resolver = self::createSUT();

        $this->assertFalse($resolver->isInternal('https://example.com/es/admin/users'));
        $this->assertFalse($resolver->isInternal('mailto:info@hydra.test'));
    }
}
