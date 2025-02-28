<?php

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NodeLister
{
    public function __construct(
        private AuthorizationCheckerInterface $checker,
        private RouterInterface $router
    ) {

    }

    public function nodes()
    {
        $nodes =  [];

        if ($this->checker->isGranted(Role::Admin->value)) {
            $nodes [] =  new Node(
                label: "Seguridad",
                icon: "solar:shield-user-line-duotone",
                items: [
                    new Node(
                        label: "Usuarios",
                        icon: "solar:users-group-rounded-line-duotone",
                        target: $this->router->generate('admin_users')
                    )
                ]
            );
        }

        return $nodes;
    }
}