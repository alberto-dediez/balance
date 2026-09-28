<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class NodeLister
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly bool $internalNotificationsEnabled = true,
        private readonly ?CurrentRolesProvider $currentRolesProvider = null,
        #[Autowire(param: 'app.announcements_enabled')]
        private readonly bool $announcementsEnabled = true,
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        if ($this->hasRole(Role::Admin)) {
            return $this->markSelected($this->toAdmins());
        }

        if ($this->hasRole(Role::Owner)) {
            return $this->markSelected($this->toOwners());
        }

        return $this->markSelected($this->toUsers());
    }

    private function hasRole(Role $role): bool
    {
        $currentRoles = $this->currentRolesProvider?->currentLoggedUserRoles();

        if (null === $currentRoles) {
            return $this->checker->isGranted($role->value);
        }

        return in_array($role, $currentRoles, true);
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<Node>
     */
    private function markSelected(array $nodes): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $nodes;
        }

        $currentPath = $request->getBaseUrl() . $request->getPathInfo();
        $bestTarget = $this->bestMatchingTarget($currentPath, $nodes);

        foreach ($nodes as $node) {
            $node->selected = null !== $bestTarget && $node->target === $bestTarget;

            foreach ($node->items as $item) {
                $item->selected = null !== $bestTarget && $item->target === $bestTarget;
                $node->selected = $node->selected || $item->selected;
            }
        }

        return $nodes;
    }

    /**
     * @param list<Node> $nodes
     */
    private function bestMatchingTarget(string $currentPath, array $nodes): ?string
    {
        $best = null;

        foreach ($nodes as $node) {
            foreach ([$node, ...$node->items] as $candidate) {
                $target = $candidate->target;

                if (null === $target || !$this->matchesCurrentPath($currentPath, $target)) {
                    continue;
                }

                if (null === $best || strlen($target) > strlen($best)) {
                    $best = $target;
                }
            }
        }

        return $best;
    }

    private function matchesCurrentPath(string $currentPath, string $target): bool
    {
        return $currentPath === $target || str_starts_with($currentPath, $target . '/');
    }

    private function libraryNode(): Node
    {
        return new Node(
            label: new TranslatableMessage('Biblioteca'),
            icon: 'solar:gallery-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Archivos'),
                    icon: 'solar:folder-with-files-line-duotone',
                    target: $this->router->generate('library_media'),
                ),
                new Node(
                    label: new TranslatableMessage('Subir archivo'),
                    icon: 'solar:upload-square-line-duotone',
                    target: $this->router->generate('library_media_upload'),
                ),
            ],
        );
    }

    private function announcementsNode(): Node
    {
        return new Node(
            label: new TranslatableMessage('Novedades'),
            icon: 'solar:notes-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Novedades'),
                    icon: 'solar:document-text-line-duotone',
                    target: $this->router->generate('announcements'),
                ),
            ],
        );
    }

    /**
     * @return list<Node>
     */
    private function toAdmins(): array
    {
        $nodes =  [];

        $nodes[] = new Node(
            label: new TranslatableMessage('Propiedades'),
            icon: 'solar:buildings-2-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Propiedades'),
                    icon: 'solar:buildings-2-line-duotone',
                    target: $this->router->generate('admin_properties'),
                ),
                new Node(
                    label: new TranslatableMessage('Empresas'),
                    icon: 'solar:case-line-duotone',
                    target: $this->router->generate('admin_companies'),
                ),
                new Node(
                    label: new TranslatableMessage('Categorías'),
                    icon: 'solar:folder-favourite-star-line-duotone',
                    target: $this->router->generate('admin_categories'),
                ),
                new Node(
                    label: new TranslatableMessage('Marcas'),
                    icon: 'solar:tag-line-duotone',
                    target: $this->router->generate('admin_brands'),
                ),
                new Node(
                    label: new TranslatableMessage('Países'),
                    icon: 'solar:globus-line-duotone',
                    target: $this->router->generate('admin_countries'),
                ),
                new Node(
                    label: new TranslatableMessage('Destinos'),
                    icon: 'solar:map-point-line-duotone',
                    target: $this->router->generate('admin_destinations'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Proyectos'),
            icon: 'solar:hand-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Proyectos'),
                    icon: 'solar:hand-heart-line-duotone',
                    target: $this->router->generate('admin_projects'),
                ),
                new Node(
                    label: new TranslatableMessage('Créditos de CO2'),
                    icon: 'solar:leaf-line-duotone',
                    target: $this->router->generate('admin_credit_purchases'),
                ),
                new Node(
                    label: new TranslatableMessage('Aportaciones'),
                    icon: 'solar:hand-money-line-duotone',
                    target: $this->router->generate('admin_project_contributions'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Tesorería'),
            icon: 'solar:wallet-money-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Panel'),
                    icon: 'solar:chart-square-line-duotone',
                    target: $this->router->generate('admin_treasury'),
                ),
                new Node(
                    label: new TranslatableMessage('Libro mayor'),
                    icon: 'solar:notebook-line-duotone',
                    target: $this->router->generate('admin_treasury_ledger'),
                ),
                new Node(
                    label: new TranslatableMessage('Beneficiarios'),
                    icon: 'solar:users-group-rounded-line-duotone',
                    target: $this->router->generate('admin_payees'),
                ),
                new Node(
                    label: new TranslatableMessage('Liquidaciones'),
                    icon: 'solar:banknote-2-line-duotone',
                    target: $this->router->generate('admin_payouts'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Materiales'),
            icon: 'solar:layers-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Materiales'),
                    icon: 'solar:layers-line-duotone',
                    target: $this->router->generate('admin_materials'),
                ),
                new Node(
                    label: new TranslatableMessage('Artículos de merchandising'),
                    icon: 'solar:bag-smile-line-duotone',
                    target: $this->router->generate('admin_merchandising_items'),
                ),
                new Node(
                    label: new TranslatableMessage('Elementos de catering'),
                    icon: 'solar:cup-hot-line-duotone',
                    target: $this->router->generate('admin_catering_items'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Instalaciones'),
            icon: 'solar:buildings-3-line-duotone',
            target: '#instalaciones',
            items: [
                new Node(
                    label: new TranslatableMessage('Combustibles'),
                    icon: 'solar:fire-line-duotone',
                    target: $this->router->generate('admin_combustibles'),
                ),
                new Node(
                    label: new TranslatableMessage('Comercializadoras eléctricas'),
                    icon: 'solar:bolt-line-duotone',
                    target: $this->router->generate('admin_electricity_marketers'),
                ),
                new Node(
                    label: new TranslatableMessage('Factores GdO'),
                    icon: 'solar:leaf-line-duotone',
                    target: $this->router->generate('admin_gdo_factors'),
                ),
                new Node(
                    label: new TranslatableMessage('Mix eléctrico'),
                    icon: 'solar:map-point-line-duotone',
                    target: $this->router->generate('admin_electricity_mixes'),
                ),
                new Node(
                    label: new TranslatableMessage('Gases refrigerantes'),
                    icon: 'solar:snowflake-line-duotone',
                    target: $this->router->generate('admin_refrigerant_gases'),
                ),
                new Node(
                    label: new TranslatableMessage('Residuos'),
                    icon: 'solar:trash-bin-minimalistic-line-duotone',
                    target: $this->router->generate('admin_wastes'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Transporte'),
            icon: 'solar:bus-line-duotone',
            target: '#transporte',
            items: [
                new Node(
                    label: new TranslatableMessage('Transporte por carretera'),
                    icon: 'solar:bus-line-duotone',
                    target: $this->router->generate('admin_road_transports'),
                ),
                new Node(
                    label: new TranslatableMessage('Combustible de maquinaria'),
                    icon: 'solar:settings-minimalistic-line-duotone',
                    target: $this->router->generate('admin_machinery_fuels'),
                ),
                new Node(
                    label: new TranslatableMessage('Movilidad'),
                    icon: 'solar:routing-2-line-duotone',
                    target: $this->router->generate('admin_mobilities'),
                ),
                new Node(
                    label: new TranslatableMessage('Pernoctaciones'),
                    icon: 'solar:sleeping-square-line-duotone',
                    target: $this->router->generate('admin_overnight_stays'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Bioscore'),
            icon: 'solar:link-circle-line-duotone',
            target: '#bioscore',
            items: [
                new Node(
                    label: new TranslatableMessage('Combustibles de instalaciones'),
                    icon: 'solar:fire-line-duotone',
                    target: $this->router->generate('admin_bioscore_facility_fuels'),
                ),
                new Node(
                    label: new TranslatableMessage('Combustibles de vehículos'),
                    icon: 'solar:bus-line-duotone',
                    target: $this->router->generate('admin_bioscore_transport_fuels'),
                ),
                new Node(
                    label: new TranslatableMessage('Gases refrigerantes'),
                    icon: 'solar:snowflake-line-duotone',
                    target: $this->router->generate('admin_bioscore_refrigerant_gases'),
                ),
                new Node(
                    label: new TranslatableMessage('Comercializadoras eléctricas'),
                    icon: 'solar:bolt-line-duotone',
                    target: $this->router->generate('admin_bioscore_electricity_marketers'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Materialidad'),
            icon: 'solar:clipboard-check-line-duotone',
            target: '#materiality',
            items: [
                new Node(
                    label: new TranslatableMessage('Categorías'),
                    icon: 'solar:list-check-line-duotone',
                    target: $this->router->generate('admin_materiality_topics'),
                ),
                new Node(
                    label: new TranslatableMessage('Preguntas'),
                    icon: 'solar:question-circle-line-duotone',
                    target: $this->router->generate('admin_materiality_questions'),
                ),
            ],
        );

        $nodes[] =  new Node(
            label: new TranslatableMessage('Administración'),
            icon: 'solar:shield-user-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Cuentas'),
                    icon: 'solar:buildings-3-line-duotone',
                    target: $this->router->generate('admin_accounts'),
                ),
                new Node(
                    label: new TranslatableMessage('Empleados'),
                    icon: 'solar:user-id-line-duotone',
                    target: $this->router->generate('admin_employees'),
                ),
                new Node(
                    label: new TranslatableMessage('Usuarios'),
                    icon: 'solar:users-group-rounded-line-duotone',
                    target: $this->router->generate('admin_users'),
                ),
                new Node(
                    label: new TranslatableMessage('Firma de documentos'),
                    icon: 'solar:verified-check-line-duotone',
                    target: $this->router->generate('admin_signature_requests'),
                ),
                new Node(
                    label: new TranslatableMessage('Informes de huella'),
                    icon: 'solar:document-text-line-duotone',
                    target: $this->router->generate('admin_carbon_footprint_reports'),
                ),
                new Node(
                    label: new TranslatableMessage('Registro'),
                    icon: 'solar:record-minimalistic-line-duotone',
                    target: $this->router->generate('admin_events'),
                ),
                new Node(
                    label: new TranslatableMessage('Grupos de coeficientes'),
                    icon: 'solar:test-tube-minimalistic-line-duotone',
                    target: $this->router->generate('admin_emission_factor_groups'),
                ),
            ],
        );

        $nodes[] = $this->libraryNode();

        if ($this->internalNotificationsEnabled) {
            $nodes[] = new Node(
                label: new TranslatableMessage('Notificaciones'),
                icon: 'solar:bell-line-duotone',
                items: [
                    new Node(
                        label: new TranslatableMessage('Mis notificaciones'),
                        icon: 'solar:bell-bing-line-duotone',
                        target: $this->router->generate('notifications'),
                    ),
                    new Node(
                        label: new TranslatableMessage('Enviar notificación'),
                        icon: 'solar:plain-line-duotone',
                        target: $this->router->generate('admin_notifications_send'),
                    ),
                    new Node(
                        label: new TranslatableMessage('Historial de envíos'),
                        icon: 'solar:history-line-duotone',
                        target: $this->router->generate('admin_notifications'),
                    ),
                ],
            );
        }

        $nodes[] = new Node(
            label: new TranslatableMessage('Novedades'),
            icon: 'solar:notes-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Novedades'),
                    icon: 'solar:notes-line-duotone',
                    target: $this->router->generate('announcements'),
                ),
                new Node(
                    label: new TranslatableMessage('Gestionar novedades'),
                    icon: 'solar:pen-new-square-line-duotone',
                    target: $this->router->generate('admin_announcements'),
                ),
                new Node(
                    label: new TranslatableMessage('Changelog'),
                    icon: 'solar:document-text-line-duotone',
                    target: $this->router->generate('admin_changelog'),
                ),
            ],
        );

        return $nodes;
    }

    /**
     * @return list<Node>
     */
    private function toOwners(): array
    {
        $nodes =  [];

        $nodes[] = new Node(
            label: new TranslatableMessage('Propiedades'),
            icon: 'solar:buildings-2-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Propiedades'),
                    icon: 'solar:buildings-2-line-duotone',
                    target: $this->router->generate('properties'),
                ),
                new Node(
                    label: new TranslatableMessage('Huellas de Carbono'),
                    icon: 'carbon:sustainability',
                    target: $this->router->generate('property_carbon_footprints'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Empresas'),
            icon: 'solar:case-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Empresas'),
                    icon: 'solar:case-line-duotone',
                    target: $this->router->generate('companies'),
                ),
                new Node(
                    label: new TranslatableMessage('Huellas de Carbono'),
                    icon: 'carbon:sustainability',
                    target: $this->router->generate('company_carbon_footprints'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Eventos'),
            icon: 'ic:twotone-event-available',
            items: [
                new Node(
                    label: new TranslatableMessage('Eventos'),
                    icon: 'ic:twotone-event-available',
                    target: $this->router->generate('events'),
                ),
                new Node(
                    label: new TranslatableMessage('Proveedores'),
                    icon: 'streamline-plump:food-truck-event-fair',
                    target: $this->router->generate('providers'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Proyectos'),
            icon: 'solar:hand-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Proyectos'),
                    icon: 'solar:hand-heart-line-duotone',
                    target: $this->router->generate('projects'),
                ),
                new Node(
                    label: new TranslatableMessage('Mis créditos'),
                    icon: 'solar:leaf-line-duotone',
                    target: $this->router->generate('credits'),
                ),
                new Node(
                    label: new TranslatableMessage('Aportaciones'),
                    icon: 'solar:hand-money-line-duotone',
                    target: $this->router->generate('project_contributions'),
                ),
                new Node(
                    label: new TranslatableMessage('Mi saldo'),
                    icon: 'solar:wallet-money-line-duotone',
                    target: $this->router->generate('my_balance'),
                ),
                new Node(
                    label: new TranslatableMessage('Mis datos de cobro'),
                    icon: 'solar:card-line-duotone',
                    target: $this->router->generate('my_payee'),
                ),
            ],
        );

        $nodes[] =  new Node(
            label: new TranslatableMessage('Administración'),
            icon: 'solar:shield-user-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Empleados'),
                    icon: 'solar:user-id-line-duotone',
                    target: $this->router->generate('employees'),
                ),
                new Node(
                    label: new TranslatableMessage('Usuarios'),
                    icon: 'solar:users-group-rounded-line-duotone',
                    target: $this->router->generate('users'),
                ),
                new Node(
                    label: new TranslatableMessage('Marca y colores'),
                    icon: 'solar:palette-round-line-duotone',
                    target: $this->router->generate('branding_edit'),
                ),
            ],
        );

        $nodes[] = $this->libraryNode();

        if ($this->internalNotificationsEnabled) {
            $nodes[] = new Node(
                label: new TranslatableMessage('Notificaciones'),
                icon: 'solar:bell-line-duotone',
                items: [
                    new Node(
                        label: new TranslatableMessage('Mis notificaciones'),
                        icon: 'solar:bell-bing-line-duotone',
                        target: $this->router->generate('notifications'),
                    ),
                ],
            );
        }

        if ($this->announcementsEnabled) {
            $nodes[] = $this->announcementsNode();
        }

        return $nodes;
    }

    /**
     * @return list<Node>
     */
    private function toUsers(): array
    {
        $nodes = [];

        $nodes[] = $this->libraryNode();

        if ($this->internalNotificationsEnabled) {
            $nodes[] = new Node(
                label: new TranslatableMessage('Notificaciones'),
                icon: 'solar:bell-line-duotone',
                items: [
                    new Node(
                        label: new TranslatableMessage('Mis notificaciones'),
                        icon: 'solar:bell-bing-line-duotone',
                        target: $this->router->generate('notifications'),
                    ),
                ],
            );
        }

        if ($this->announcementsEnabled) {
            $nodes[] = $this->announcementsNode();
        }

        return $nodes;
    }
}
