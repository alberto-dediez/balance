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

        foreach ($nodes as $node) {
            $node->selected = $this->matchesCurrentPath($currentPath, $node->target);

            foreach ($node->items as $item) {
                $item->selected = $this->matchesCurrentPath($currentPath, $item->target);
                $node->selected = $node->selected || $item->selected;
            }
        }

        return $nodes;
    }

    private function matchesCurrentPath(string $currentPath, ?string $target): bool
    {
        if (null === $target) {
            return false;
        }

        return $currentPath === $target || str_starts_with($currentPath, $target . '/');
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

        return $nodes;
    }

    /**
     * @return list<Node>
     */
    private function toUsers(): array
    {
        return [];
    }
}
