<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsRequest;
use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsService;
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

        return $this->render('@carbon_footprint/property_trip_calculations/results.html.twig', [
            'property' => $response->property,
            'aggregation' => $response->aggregation,
            'mobilityBreakdown' => $response->mobilityBreakdown,
            'contributions' => $response->contributions,
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
        ]);
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
