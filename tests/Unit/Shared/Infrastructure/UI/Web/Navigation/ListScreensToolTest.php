<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Infrastructure\UI\Web\Navigation\ListScreensTool;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ListScreensToolTest extends TestCase
{
    public function testIsAReadToolAvailableToUsersWithAMenu(): void
    {
        $sut = $this->createSUT();

        $this->assertSame('list_screens', $sut->name());
        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
    }

    public function testWithoutTermReturnsEveryLeafWithItsLocationAndHref(): void
    {
        $sut = $this->createSUT();

        $result = $sut->execute($this->arguments($sut, []), UserMother::adminUser(), $this->context());
        $screens = $result->data['screens'];

        $this->assertContains(['title' => 'Cuentas', 'location' => 'Seguridad > Organización', 'href' => 'route:admin_accounts'], $screens);
        $this->assertContains(['title' => 'Usuarios', 'location' => 'Seguridad > Organización', 'href' => 'route:admin_users'], $screens);
        $this->assertContains(['title' => 'Archivos', 'location' => 'Biblioteca', 'href' => 'route:library_media'], $screens);
        $this->assertSame(sprintf('%d pantallas', count($screens)), $result->summary);
    }

    public function testTheTermIgnoresCaseAndAccents(): void
    {
        $sut = $this->createSUT();

        $result = $sut->execute($this->arguments($sut, ['term' => 'ORGANIZACION usu']), UserMother::adminUser(), $this->context());

        $this->assertSame([['title' => 'Usuarios', 'location' => 'Seguridad > Organización', 'href' => 'route:admin_users']], $result->data['screens']);
    }

    public function testALeafWhoseTargetMatchesNoRouteIsSkipped(): void
    {
        $sut = $this->createSUT(unmatched: ['/es/admin/events']);

        $result = $sut->execute($this->arguments($sut, ['term' => 'Auditoría']), UserMother::adminUser(), $this->context());

        $this->assertSame(['Firma de documentos'], array_column($result->data['screens'], 'title'));
    }

    public function testTheInternalParametersAreNotInTheHref(): void
    {
        $sut = $this->createSUT();

        $result = $sut->execute($this->arguments($sut, ['term' => 'usuarios']), UserMother::adminUser(), $this->context());

        $this->assertStringNotContainsString('_locale', $result->data['screens'][0]['href']);
        $this->assertStringNotContainsString('_controller', $result->data['screens'][0]['href']);
    }

    public function testAnAdminDoesNotGetTheSentEmailsButASuperAdminDoes(): void
    {
        $sut = $this->createSUT();

        $this->assertNotContains('Emails enviados', $this->titles($sut, UserMother::adminUser()));
        $this->assertContains('Emails enviados', $this->titles($sut, UserMother::superAdminUser()));
    }

    public function testAGetOnlyScreenIsListedWhenTheContextIsAPost(): void
    {
        $context = new RequestContext(method: 'POST');
        $sut = $this->createSUT(getOnly: ['/es/admin/events'], context: $context);

        $result = $sut->execute($this->arguments($sut, ['term' => 'Registro']), UserMother::adminUser(), $this->context());

        $this->assertSame(['Registro'], array_column($result->data['screens'], 'title'));
        $this->assertSame('POST', $context->getMethod());
    }

    /** @return list<string> */
    private function titles(ListScreensTool $sut, User $user): array
    {
        return array_column($sut->execute($this->arguments($sut, []), $user, $this->context())->data['screens'], 'title');
    }

    /**
     * @param list<string> $unmatched
     * @param list<string> $getOnly
     */
    private function createSUT(array $unmatched = [], array $getOnly = [], ?RequestContext $context = null): ListScreensTool
    {
        $paths = [];
        $context ??= new RequestContext();
        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($context);

        $router->method('generate')->willReturnCallback(
            static function (string $name) use (&$paths): string {
                $path = '/es/' . str_replace('_', '/', $name);
                $paths[$path] = $name;

                return $path;
            },
        );

        $router->method('match')->willReturnCallback(
            static function (string $path) use (&$paths, $unmatched, $getOnly, $context): array {
                if (!isset($paths[$path]) || in_array($path, $unmatched, true)) {
                    throw new ResourceNotFoundException();
                }

                if (in_array($path, $getOnly, true) && 'GET' !== $context->getMethod()) {
                    throw new MethodNotAllowedException(['GET']);
                }

                return ['_route' => $paths[$path], '_locale' => 'es', '_controller' => 'Controller'];
            },
        );

        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(false);

        return new ListScreensTool(new NodeLister($checker, $router, new RequestStack(), roleHierarchy: NodeListerTest::roleHierarchy()), $router, new FakeTranslator());
    }

    /** @param array<string, mixed> $raw */
    private function arguments(ListScreensTool $sut, array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
