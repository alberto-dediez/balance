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

        $this->assertSame(['Tipos de cambio', 'Cuentas', 'Empleados', 'Usuarios', 'Combustibles de instalaciones', 'Combustibles de vehículos', 'Gases refrigerantes', 'Comercializadoras eléctricas', 'Firma de documentos', 'Registro'], $this->titles($results));
        $this->assertSame(['Administración', 'Administración > Organización', 'Administración > Organización', 'Administración > Organización', 'Administración > Bioscore', 'Administración > Bioscore', 'Administración > Bioscore', 'Administración > Bioscore', 'Administración > Auditoría', 'Administración > Auditoría'], array_map(fn(SearchResult $result): ?string => $result->subtitle, $results));
        $this->assertSame(
            ['/es/admin/exchange/rates', '/es/admin/accounts', '/es/admin/employees', '/es/admin/users', '/es/admin/bioscore/facility/fuels', '/es/admin/bioscore/transport/fuels', '/es/admin/bioscore/refrigerant/gases', '/es/admin/bioscore/electricity/marketers', '/es/admin/signature-requests', '/es/admin/events'],
            $this->urls($results),
        );
        $this->assertSame('solar:dollar-minimalistic-line-duotone', $results[0]->icon);
        $this->assertNull($results[0]->route);
    }

    public function testAGroupLabelFindsOnlyItsLeaves(): void
    {
        $sut = $this->createSUT(Role::Admin);

        $this->assertSame(['Firma de documentos', 'Registro'], $this->titles($sut->search(new SearchTerm('Auditoría'), UserMother::adminUser(), 5)));
        $this->assertSame(['Cuentas', 'Empleados', 'Usuarios'], $this->titles($sut->search(new SearchTerm('Organización'), UserMother::adminUser(), 5)));
    }

    public function testAMasterGroupLabelFindsOnlyItsLeaves(): void
    {
        $results = $this->createSUT(Role::Admin)->search(new SearchTerm('Instalaciones'), UserMother::adminUser(), 5);
        $masters = array_values(array_filter($results, static fn(SearchResult $result): bool => 'Maestros > Instalaciones' === $result->subtitle));

        $this->assertSame(['Combustibles', 'Comercializadoras eléctricas', 'Factores GdO', 'Mix eléctrico', 'Gases refrigerantes', 'Residuos'], $this->titles($masters));
        $this->assertSame(['Combustibles de instalaciones'], array_values(array_diff($this->titles($results), $this->titles($masters))));
        $this->assertNotContains('Materiales', $this->titles($results));
        $this->assertNotContains('Procesos', $this->titles($results));
    }

    public function testTheMastersRootFindsEveryMaster(): void
    {
        $results = $this->createSUT(Role::Admin)->search(new SearchTerm('Maestros'), UserMother::adminUser(), 5);
        $titles = $this->titles($results);

        $this->assertCount(18, $results);
        $this->assertNotContains('Maestros', $titles);

        foreach (['Productos vendidos', 'Inversiones', 'Instalaciones', 'Transporte'] as $group) {
            $this->assertNotContains($group, $titles);
        }

        $this->assertCount(1, array_keys($titles, 'Materiales', true));

        foreach ($results as $result) {
            $this->assertContains($result->subtitle, ['Maestros > Materiales', 'Maestros > Productos vendidos', 'Maestros > Inversiones', 'Maestros > Instalaciones', 'Maestros > Transporte']);
        }
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

        $this->assertSame(['Empleados', 'Usuarios', 'Distribuidores', 'Marca y colores'], $this->titles($results));
        $this->assertSame(['/es/employees', '/es/users', '/es/distributors', '/es/branding/edit'], $this->urls($results));
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

        $this->assertSame(['Marcas', 'Beneficiarios', 'Artículos de merchandising', 'Combustible de maquinaria', 'Informes de huella', 'Grupos de coeficientes', 'Medidas', 'Categorías de medidas', 'Categorías', 'Preguntas', 'Sectores', 'Configuración', 'Usuarios', 'Archivos', 'Subir archivo', 'Enviar notificación', 'Gestionar novedades'], $this->titles($results));
    }

    public function testTranslatesTheEntriesToTheCurrentLocale(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['Administración' => 'Administration', 'Empleados' => 'Employees', 'Usuarios' => 'Users'], 'en');

        $sut = $this->createSUT(Role::Owner, translator: $translator);

        $results = $sut->search(new SearchTerm('administration'), UserMother::adminUser(), 5);

        $this->assertSame(['Employees', 'Users', 'Distribuidores', 'Marca y colores'], $this->titles($results));
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

        $this->assertSame(['/es/employees', '/es/users', '/es/distributors', '/es/branding/edit'], $this->urls($results));
    }

    public function testALeafOfAGroupHasThePathAsSubtitle(): void
    {
        $results = $this->createThreeLevelSUT()->search(new SearchTerm('B1'), UserMother::adminUser(), 5);

        $this->assertSame(['B1'], $this->titles($results));
        $this->assertSame('Raíz > Grupo B', $results[0]->subtitle);
        $this->assertSame(['/es/b1'], $this->urls($results));
        $this->assertSame('solar:record-minimalistic-line-duotone', $results[0]->icon);
    }

    public function testGroupsAndRootsAreNotResults(): void
    {
        $sut = $this->createThreeLevelSUT();

        $this->assertNotContains('/es/grupo-a', $this->urls($sut->search(new SearchTerm('Grupo A'), UserMother::adminUser(), 5)));
        $this->assertSame(['Suelta', 'A1', 'A2', 'B1'], $this->titles($sut->search(new SearchTerm('Raíz'), UserMother::adminUser(), 5)));
    }

    public function testARootLeafHasNoSubtitle(): void
    {
        $results = $this->createThreeLevelSUT()->search(new SearchTerm('Inicio'), UserMother::adminUser(), 5);

        $this->assertSame(['Inicio'], $this->titles($results));
        $this->assertNull($results[0]->subtitle);
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

    private function createThreeLevelSUT(): NavigationSearchProvider
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/es/search'));

        $nodeLister = new ThreeLevelNodeLister(FakeSecurity::fromRole(Role::Admin), $this->createMock(RouterInterface::class), $requestStack);

        return new NavigationSearchProvider($nodeLister, new FakeTranslator());
    }
}
