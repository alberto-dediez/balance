<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsRequest;
use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsService;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\View\ResultsView;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip-calculations/{propertyId}/responses', name: 'carbon_footprint_property_trip_calculations_responses', methods: ['GET'])]
class ResultsController extends AbstractController
{
    public function __invoke(
        string $propertyId,
        GetTripCalculationResultsService $getResults,
        ResultsView $resultsView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $from = $this->date($request->query->get('from'));
        $to = $this->date($request->query->get('to'), endOfDay: true);

        try {
            $response = $getResults(
                new GetTripCalculationResultsRequest(new PropertyId($propertyId), $from, $to),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/property_trip_calculations/results.html.twig',
            $resultsView->build($response, $this->queryString($request, 'from'), $this->queryString($request, 'to')),
        );
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query->get($key);

        return is_string($value) ? $value : null;
    }

    private function date(mixed $value, bool $endOfDay = false): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if (false === $date) {
            return null;
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date;
    }
}
