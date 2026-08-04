<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
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
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        if ($this->checker->isGranted(Role::Admin->value)) {
            return $this->markSelected($this->toAdmins());
        }

        if ($this->checker->isGranted(Role::Owner->value)) {
            return $this->markSelected($this->toOwners());
        }

        return $this->markSelected($this->toUsers());
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
     * When several targets match the current path (e.g. /delivery-logs and
     * /delivery-logs/export), only the most specific one must be selected.
     *
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
                    label: new TranslatableMessage('Empresas'),
                    icon: 'solar:case-line-duotone',
                    target: $this->router->generate('companies'),
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
                    label: new TranslatableMessage('Historial de solicitudes'),
                    icon: 'solar:history-line-duotone',
                    target: $this->router->generate('credit_purchases'),
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

        return $nodes;
    }
}
