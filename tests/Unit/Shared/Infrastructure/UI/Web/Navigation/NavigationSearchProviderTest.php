<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;
use App\Shared\Infrastructure\UI\Web\Navigation\NavigationSearchProvider;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeSecurity;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationSearchProviderTest extends TestCase
{
    public function testAnAdminFindsEveryEntryOfAGroupByItsName(): void
    {
        $sut = $this->createSUT(Role::Admin, routes: ['admin_signature_requests' => '/es/admin/signature-requests']);

        $results = $sut->search(new SearchTerm('Administración'), UserMother::adminUser(), 5);

        $this->assertSame(['Cuentas', 'Empleados', 'Usuarios', 'Firma de documentos', 'Informes de huella', 'Registro', 'Grupos de coeficientes', 'Tipos de cambio'], $this->titles($results));
        $this->assertSame(array_fill(0, 8, 'Administración'), array_map(fn(SearchResult $result): ?string => $result->subtitle, $results));
        $this->assertSame(
            ['/es/admin/accounts', '/es/admin/employees', '/es/admin/users', '/es/admin/signature-requests', '/es/admin/carbon/footprint/reports', '/es/admin/events', '/es/admin/emission/factor/groups', '/es/admin/exchange/rates'],
            $this->urls($results),
        );
        $this->assertSame('solar:buildings-3-line-duotone', $results[0]->icon);
        $this->assertNull($results[0]->route);
    }

    public function testTheMatchIgnoresCaseAndAccents(): void
    {
        $sut = $this->createSUT(Role::Admin);

        $this->assertContains('Mis notificaciones', $this->titles($sut->search(new SearchTerm('NOTIFICACION'), UserMother::adminUser(), 5)));
        $this->assertContains('Historial de envíos', $this->titles($sut->search(new SearchTerm('envio'), UserMother::adminUser(), 5)));
    }

    public function testEveryWordOfTheTermMustMatch(): void
    {
        $sut = $this->createSUT(Role::Admin);

        $this->assertSame(['Usuarios'], $this->titles($sut->search(new SearchTerm('admin usu'), UserMother::adminUser(), 5)));
        $this->assertSame([], $sut->search(new SearchTerm('administracion biblioteca'), UserMother::adminUser(), 5));
    }

    public function testAnOwnerOnlySeesItsOwnMenu(): void
    {
        $sut = $this->createSUT(Role::Owner);

        $results = $sut->search(new SearchTerm('Administración'), UserMother::adminUser(), 5);

        $this->assertSame(['Empleados', 'Usuarios', 'Marca y colores'], $this->titles($results));
        $this->assertSame(['/es/employees', '/es/users', '/es/branding/edit'], $this->urls($results));
        $this->assertSame([], $sut->search(new SearchTerm('Cuentas'), UserMother::adminUser(), 5));
    }

    public function testAUserSeesTheLibraryAndTheProviderIsAvailable(): void
    {
        $sut = $this->createSUT(Role::User);
        $user = UserMother::registeredUser();

        $this->assertTrue($sut->isAvailableTo($user));

        $results = $sut->search(new SearchTerm('archivos'), $user, 5);

        $this->assertSame(['Archivos'], $this->titles($results));
        $this->assertSame(['/es/library/media'], $this->urls($results));
        $this->assertSame('Biblioteca', $results[0]->subtitle);
        $this->assertSame([], $sut->search(new SearchTerm('Administración'), $user, 5));
    }

    public function testGroupsWithoutTargetAreNotResults(): void
    {
        $results = $this->createSUT(Role::Admin)->search(new SearchTerm('Administración'), UserMother::adminUser(), 5);

        $this->assertNotContains('Administración', $this->titles($results));
    }

    public function testDisabledFeaturesDisappear(): void
    {
        $sut = $this->createSUT(Role::Admin, internalNotificationsEnabled: false);

        $this->assertSame([], $sut->search(new SearchTerm('notificaciones'), UserMother::adminUser(), 5));
    }

    public function testIgnoresTheLimit(): void
    {
        $results = $this->createSUT(Role::Admin)->search(new SearchTerm('ar'), UserMother::adminUser(), 1);

        $this->assertSame(['Marcas', 'Beneficiarios', 'Artículos de merchandising', 'Combustible de maquinaria', 'Usuarios', 'Archivos', 'Subir archivo', 'Enviar notificación', 'Gestionar novedades'], $this->titles($results));
    }

    public function testTranslatesTheEntriesToTheCurrentLocale(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['Administración' => 'Administration', 'Empleados' => 'Employees', 'Usuarios' => 'Users'], 'en');

        $sut = $this->createSUT(Role::Owner, translator: $translator);

        $results = $sut->search(new SearchTerm('administration'), UserMother::adminUser(), 5);

        $this->assertSame(['Employees', 'Users', 'Marca y colores'], $this->titles($results));
        $this->assertSame('Administration', $results[0]->subtitle);
        $this->assertSame([], $sut->search(new SearchTerm('Administración'), UserMother::adminUser(), 5));
    }

    public function testCategoryIsTheFirstOne(): void
    {
        $category = $this->createSUT(Role::Admin)->category();

        $this->assertSame(0, $category->position);
        $this->assertSame('navigation', $category->key);
    }

    public function testIgnoresTheUserParameter(): void
    {
        $results = $this->createSUT(Role::Owner)->search(new SearchTerm('Administración'), UserMother::adminUser(), 5);

        $this->assertSame(['/es/employees', '/es/users', '/es/branding/edit'], $this->urls($results));
    }

    /**
     * @param list<SearchResult> $results
     *
     * @return list<string>
     */
    private function titles(array $results): array
    {
        return array_map(fn(SearchResult $result): string => $result->title, $results);
    }

    /**
     * @param list<SearchResult> $results
     *
     * @return list<?string>
     */
    private function urls(array $results): array
    {
        return array_map(fn(SearchResult $result): ?string => $result->url, $results);
    }

    /**
     * @param array<string, string> $routes
     */
    private function createSUT(
        Role $role,
        array $routes = [],
        ?TranslatorInterface $translator = null,
        bool $internalNotificationsEnabled = true,
        bool $announcementsEnabled = true,
    ): NavigationSearchProvider {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')->willReturnCallback(
            static fn(string $name): string => $routes[$name] ?? '/es/' . str_replace('_', '/', $name),
        );

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/es/search'));

        $nodeLister = new NodeLister(
            FakeSecurity::fromRole($role),
            $router,
            $requestStack,
            internalNotificationsEnabled: $internalNotificationsEnabled,
            announcementsEnabled: $announcementsEnabled,
        );

        return new NavigationSearchProvider($nodeLister, $translator ?? new FakeTranslator());
    }
}
