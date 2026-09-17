<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsRequest;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/admin/bioscore/{catalog}/sync',
    name: 'admin_bioscore_catalogs_sync',
    requirements: ['catalog' => 'facility_fuel|transport_fuel|refrigerant_gas|electricity_marketer'],
    methods: ['GET'],
)]
class SyncController extends AbstractController
{
    public function __invoke(
        string $catalog,
        BioscoreCatalogItemAuthorizer $authorizer,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $bioscoreCatalog = BioscoreCatalog::from($catalog);

        try {
            $authorizer->denyUnlessCanDoThat(
                new BioscoreCatalogItem(id: BioscoreCatalogItemId::generate()),
                SecurityAction::Admin,
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if (!$this->isCsrfTokenValid('bioscore_sync', $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(new SyncBioscoreCatalogsRequest(now: new \DateTimeImmutable('NOW')), Priority::MEDIUM),
            $loggedUser(),
        );

        $this->addFlash('success', new TranslatableMessage('Sincronización de maestros de Bioscore encolada'));

        return $this->redirectToRoute(BioscoreCatalogRoutes::routeName($bioscoreCatalog));
    }
}
