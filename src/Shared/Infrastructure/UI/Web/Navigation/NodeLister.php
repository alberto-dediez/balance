<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Translation\TranslatableMessage;

class NodeLister
{
    /** @var list<Role>|null */
    private ?array $roles = null;

    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly bool $internalNotificationsEnabled = true,
        private readonly ?CurrentRolesProvider $currentRolesProvider = null,
        #[Autowire(param: 'app.announcements_enabled')]
        private readonly bool $announcementsEnabled = true,
        #[Autowire(param: 'assistant.enabled')]
        private readonly bool $assistantEnabled = false,
        private readonly ?RoleHierarchyInterface $roleHierarchy = null,
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        return $this->markSelected($this->roots());
    }

    /**
     * @return list<Node>
     */
    public function nodesFor(User $user): array
    {
        $this->roles = array_values($user->roles());

        try {
            return $this->roots();
        } finally {
            $this->roles = null;
        }
    }

    /**
     * @return list<Node>
     */
    protected function roots(): array
    {
        if ($this->hasRole(Role::Admin)) {
            return $this->toAdmins();
        }

        if ($this->hasRole(Role::Owner)) {
            return $this->toOwners();
        }

        return $this->toUsers();
    }

    private function hasRole(Role $role): bool
    {
        if (null !== $this->roles) {
            $names = array_map(static fn(Role $userRole): string => $userRole->value, $this->roles);
            $reachable = null === $this->roleHierarchy ? $names : $this->roleHierarchy->getReachableRoleNames($names);

            return in_array($role->value, $reachable, true);
        }

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
            $this->select($node, $bestTarget);
        }

        return $nodes;
    }

    private function select(Node $node, ?string $bestTarget): bool
    {
        if ([] === $node->items) {
            $node->selected = null !== $bestTarget && $node->target === $bestTarget;

            return $node->selected;
        }

        $selected = false;

        foreach ($node->items as $item) {
            $selected = $this->select($item, $bestTarget) || $selected;
        }

        $node->selected = $selected;

        return $node->selected;
    }

    /**
     * @param list<Node> $nodes
     */
    private function bestMatchingTarget(string $currentPath, array $nodes): ?string
    {
        $best = null;

        foreach ($this->leaves($nodes) as $leaf) {
            $target = $leaf->target;

            if (null === $target || !$this->matchesCurrentPath($currentPath, $target)) {
                continue;
            }

            if (null === $best || strlen($target) > strlen($best)) {
                $best = $target;
            }
        }

        return $best;
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<Node>
     */
    private function leaves(array $nodes): array
    {
        $leaves = [];

        foreach ($nodes as $node) {
            if ([] === $node->items) {
                $leaves[] = $node;

                continue;
            }

            $leaves = [...$leaves, ...$this->leaves($node->items)];
        }

        return $leaves;
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
                    label: new TranslatableMessage('Carpetas'),
                    icon: 'solar:folder-line-duotone',
                    target: $this->router->generate('library_folders'),
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

        $auditItems = [
            new Node(
                label: new TranslatableMessage('Firma de documentos'),
                icon: 'solar:verified-check-line-duotone',
                target: $this->router->generate('admin_signature_requests'),
            ),
        ];

        if ($this->hasRole(Role::SuperAdmin)) {
            $auditItems[] = new Node(
                label: new TranslatableMessage('Emails enviados'),
                icon: 'solar:letter-opened-line-duotone',
                target: $this->router->generate('admin_emails'),
            );
        }

        $auditItems[] = new Node(
            label: new TranslatableMessage('Registro'),
            icon: 'solar:record-minimalistic-line-duotone',
            target: $this->router->generate('admin_events'),
        );

        if ($this->assistantEnabled) {
            $auditItems[] = new Node(
                label: new TranslatableMessage('Consumo del asistente'),
                icon: 'solar:pie-chart-2-line-duotone',
                target: $this->router->generate('admin_assistant_usage'),
            );
        }

        $nodes[] =  new Node(
            label: new TranslatableMessage('Seguridad'),
            icon: 'solar:shield-user-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Organización'),
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
                    ],
                ),
                new Node(
                    label: new TranslatableMessage('Auditoría'),
                    items: $auditItems,
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
