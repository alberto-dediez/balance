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

        $nodes[] =  new Node(
            label: new TranslatableMessage('Seguridad'),
            icon: 'solar:shield-user-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Cuentas'),
                    icon: 'solar:buildings-3-line-duotone',
                    target: $this->router->generate('admin_accounts'),
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

        $nodes[] =  new Node(
            label: new TranslatableMessage('Seguridad'),
            icon: 'solar:shield-user-line-duotone',
            items: [
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
