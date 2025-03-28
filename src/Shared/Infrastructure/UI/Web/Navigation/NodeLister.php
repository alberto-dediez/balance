<?php

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;
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
        if ($this->checker->isGranted(Role::Admin->value)) {
            return $this->toAdmins();
        } 
        
        if ($this->checker->isGranted(Role::Owner->value)) {
            return $this->toOwners();
        }

        return $this->toUsers();
    }

    private function toAdmins()
    {
        $nodes =  [];

        $nodes [] = new Node(
            label: new TranslatableMessage("Propiedades"),
            icon: "solar:buildings-2-line-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Propiedades"),
                    icon: "solar:buildings-2-line-duotone",
                    target: $this->router->generate('admin_properties')
                )
            ]
        );

        $nodes [] = new Node(
            label: new TranslatableMessage("Residuos"),
            icon: "ph:recycle-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Tipologias"),
                    icon: "solar:folder-with-files-line-duotone",
                    target: $this->router->generate('admin_wastes')
                )
            ]
        );

        $nodes [] =  new Node(
            label: new TranslatableMessage("Seguridad"),
            icon: "solar:shield-user-line-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Cuentas"),
                    icon: "solar:buildings-3-line-duotone",
                    target: $this->router->generate('admin_accounts')
                ),
                new Node(
                    label: new TranslatableMessage("Usuarios"),
                    icon: "solar:users-group-rounded-line-duotone",
                    target: $this->router->generate('admin_users')
                ),
                new Node(
                    label: new TranslatableMessage("Registro"),
                    icon: "solar:record-minimalistic-line-duotone",
                    target: $this->router->generate('admin_events')
                ),
            ]
        );

        return $nodes;
    }

    private function toOwners()
    {
        $nodes =  [];

        $nodes [] = new Node(
            label: new TranslatableMessage("Propiedades"),
            icon: "solar:buildings-2-line-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Propiedades"),
                    icon: "solar:buildings-2-line-duotone",
                    target: $this->router->generate('properties')
                )
            ]
        );

        $nodes [] = new Node(
            label: new TranslatableMessage("Residuos"),
            icon: "ph:recycle-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Contenedores"),
                    icon: "solar:paper-bin-line-duotone",
                    target: $this->router->generate('containers')
                ),
                new Node(
                    label: new TranslatableMessage("Mediciones"),
                    icon: "solar:ruler-line-duotone",
                    target: $this->router->generate('measurements')
                )
            ]
        );

        $nodes [] =  new Node(
            label: new TranslatableMessage("Seguridad"),
            icon: "solar:shield-user-line-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Usuarios"),
                    icon: "solar:users-group-rounded-line-duotone",
                    target: $this->router->generate('users')
                ),
            ]
        );

        return $nodes;
    }

    private function toUsers()
    {
        $nodes = [];



        $nodes [] = new Node(
            label: new TranslatableMessage("Residuos"),
            icon: "ph:recycle-duotone",
            items: [
                new Node(
                    label: new TranslatableMessage("Mediciones"),
                    icon: "solar:ruler-line-duotone",
                    target: $this->router->generate('measurements')
                )
            ]
        );

        return $nodes;
    }
}