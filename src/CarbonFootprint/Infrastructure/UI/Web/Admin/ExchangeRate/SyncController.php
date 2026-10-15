<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ExchangeRate;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesRequest;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/exchange-rates/sync', name: 'admin_exchange_rates_sync', methods: ['GET'])]
#[CsrfProtected('exchange_rates_sync')]
class SyncController extends AbstractController
{
    public function __invoke(
        ExchangeRateAuthorizer $authorizer,
        LoggedUser $loggedUser,
    ): Response {
        try {
            $authorizer->denyUnlessCanDoThat(
                new ExchangeRate(id: ExchangeRateId::generate()),
                SecurityAction::Admin,
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(new SyncExchangeRatesRequest(now: new \DateTimeImmutable('NOW')), Priority::MEDIUM),
            $loggedUser(),
        );

        $this->addFlash('success', new TranslatableMessage('Sincronización de tipos de cambio encolada'));

        return $this->redirectToRoute('admin_exchange_rates');
    }
}
