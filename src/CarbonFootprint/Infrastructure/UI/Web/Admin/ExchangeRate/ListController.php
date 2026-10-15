<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ExchangeRate;

use App\CarbonFootprint\Application\ExchangeRate\List\ListExchangeRatesRequest;
use App\CarbonFootprint\Application\ExchangeRate\List\ListExchangeRatesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListExchangeRatesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/exchange-rates', name: 'admin_exchange_rates')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListExchangeRatesService $listExchangeRates, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListExchangeRatesRequest();
            }, ListExchangeRatesType::class);

            $response = $listExchangeRates($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/exchange-rates/list.html.twig', [
            'exchangeRates' => $response->exchangeRates,
            'totalResults' => $response->totalResults,
            'canSync' => $response->canSync,
            'form' => $form->createView(),
        ]);
    }
}
