<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class NodeLister
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly RouterInterface $router,
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        if ($this->checker->isGranted(Role::Admin->value)) {
            return $this->toAdmins();
        }

        if ($this->checker->isGranted(Role::Owner->value)) {
            return $this->toOwners();
        }

        return $this->toUsers();
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
            label: new TranslatableMessage('Jerarquía de prioridades'),
            icon: 'solar:danger-triangle-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Acciones'),
                    icon: 'solar:hand-heart-line-duotone',
                    target: $this->router->generate('admin_actions'),
                ),
                new Node(
                    label: new TranslatableMessage('Motivos'),
                    icon: 'solar:shield-cross-line-duotone',
                    target: $this->router->generate('admin_reasons'),
                ),
                new Node(
                    label: new TranslatableMessage('Categorías de servicio'),
                    icon: 'solar:folder-with-files-line-duotone',
                    target: $this->router->generate('admin_service_categories'),
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
                    label: new TranslatableMessage('Usuarios'),
                    icon: 'solar:users-group-rounded-line-duotone',
                    target: $this->router->generate('admin_users'),
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

        $nodes[] = new Node(
            label: new TranslatableMessage('Propiedades'),
            icon: 'solar:buildings-2-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Propiedades'),
                    icon: 'solar:buildings-2-line-duotone',
                    target: $this->router->generate('properties'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Jerarquía de prioridades'),
            icon: 'solar:danger-triangle-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Puntos críticos'),
                    icon: 'ph:cooking-pot-duotone',
                    target: $this->router->generate('critical_points'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Plan de Prevención'),
            icon: 'solar:shield-check-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Planes de Prevención'),
                    icon: 'solar:document-text-line-duotone',
                    target: $this->router->generate('prevention_plans'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Donaciones'),
            icon: 'solar:hand-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Entidades Receptoras'),
                    icon: 'solar:buildings-2-line-duotone',
                    target: $this->router->generate('recipient_organizations'),
                ),
                new Node(
                    label: new TranslatableMessage('Registro de entregas'),
                    icon: 'solar:record-minimalistic-line-duotone',
                    target: $this->router->generate('delivery_logs'),
                ),
                new Node(
                    label: new TranslatableMessage('Convenios de donación'),
                    icon: 'solar:file-text-line-duotone',
                    target: $this->router->generate('donation_agreements'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Doggy Bag'),
            icon: 'solar:bag-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Envases'),
                    icon: 'solar:box-line-duotone',
                    target: $this->router->generate('packagings'),
                ),
                new Node(
                    label: new TranslatableMessage('Carteles'),
                    icon: 'solar:bill-list-line-duotone',
                    target: $this->router->generate('doggy_bag_signs'),
                ),
                new Node(
                    label: new TranslatableMessage('Excepciones'),
                    icon: 'solar:shield-warning-line-duotone',
                    target: $this->router->generate('doggy_bag_exceptions'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Acciones formativas e informativas'),
            icon: 'solar:book-2-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Protocolos'),
                    icon: 'solar:file-text-line-duotone',
                    target: $this->router->generate('protocols'),
                ),
                new Node(
                    label: new TranslatableMessage('Sesiones formativas'),
                    icon: 'solar:bill-list-line-duotone',
                    target: $this->router->generate('training_sessions'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Incidencias'),
            icon: 'solar:shield-warning-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Incidencias'),
                    icon: 'solar:shield-warning-line-duotone',
                    target: $this->router->generate('incidences'),
                ),
            ],
        );

        $nodes[] =  new Node(
            label: new TranslatableMessage('Administración'),
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
        $nodes = [];

        $nodes[] = new Node(
            label: new TranslatableMessage('Donaciones'),
            icon: 'solar:hand-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Registro de entregas'),
                    icon: 'solar:record-minimalistic-line-duotone',
                    target: $this->router->generate('delivery_logs'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Doggy Bag'),
            icon: 'solar:bag-heart-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Carteles'),
                    icon: 'solar:bill-list-line-duotone',
                    target: $this->router->generate('doggy_bag_signs'),
                ),
                new Node(
                    label: new TranslatableMessage('Excepciones'),
                    icon: 'solar:shield-warning-line-duotone',
                    target: $this->router->generate('doggy_bag_exceptions'),
                ),
            ],
        );

        $nodes[] = new Node(
            label: new TranslatableMessage('Acciones formativas e informativas'),
            icon: 'solar:book-2-line-duotone',
            items: [
                new Node(
                    label: new TranslatableMessage('Protocolos'),
                    icon: 'solar:file-text-line-duotone',
                    target: $this->router->generate('protocols'),
                ),
                new Node(
                    label: new TranslatableMessage('Sesiones formativas'),
                    icon: 'solar:bill-list-line-duotone',
                    target: $this->router->generate('training_sessions'),
                ),
            ],
        );

        return $nodes;
    }
}
