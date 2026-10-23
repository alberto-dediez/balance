<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\AccountTripCalculator;

use App\CarbonFootprint\Application\TripCalculation\GetAccountResults\GetAccountTripCalculatorResultsRequest;
use App\CarbonFootprint\Application\TripCalculation\GetAccountResults\GetAccountTripCalculatorResultsService;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\ResultsDateRangeTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\View\ResultsView;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/trip-calculator/responses', name: 'carbon_footprint_trip_calculator_responses', methods: ['GET'])]
class ResultsController extends AbstractController
{
    use ResultsDateRangeTrait;

    public function __invoke(
        GetAccountTripCalculatorResultsService $getAccountResults,
        ResultsView $resultsView,
        CurrentAccountIdProvider $currentAccountIdProvider,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $from = $this->date($request->query->get('from'));
        $to = $this->date($request->query->get('to'), endOfDay: true);

        try {
            $response = $getAccountResults(new GetAccountTripCalculatorResultsRequest($from, $to), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/account_trip_calculator/results.html.twig',
            $resultsView->build($response, $this->queryString($request, 'from'), $this->queryString($request, 'to')) + [
                'accountId' => (string) $currentAccountIdProvider->currentAccountId(),
            ],
        );
    }
}
