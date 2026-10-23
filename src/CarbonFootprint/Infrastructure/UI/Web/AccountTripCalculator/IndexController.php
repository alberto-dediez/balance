<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\AccountTripCalculator;

use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkService;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\TripCalculationScopeForm;
use App\CarbonFootprint\Infrastructure\UI\Web\PublicLanding\PublicLandingForm;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/trip-calculator', name: 'carbon_footprint_trip_calculator', methods: ['GET', 'POST'])]
class IndexController extends AbstractController
{
    public function __invoke(
        RegisterAccountTripCalculationLinkService $registerAccountTripCalculationLink,
        TripCalculationScopeForm $tripCalculationScopeForm,
        TripCalculationDestinationForm $tripCalculationDestinationForm,
        PublicLandingForm $publicLandingForm,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            $tripCalculationLink = $registerAccountTripCalculationLink(new RegisterAccountTripCalculationLinkRequest(), $user)->tripCalculationLink;

            $scopeForm = $tripCalculationScopeForm->handleForLink($tripCalculationLink, $user, $request);
            $destinationForm = $tripCalculationDestinationForm->handle($tripCalculationLink, $user, $request);
            $landingForm = $publicLandingForm->handle(
                PublicLandingOwnerType::TripCalculationLink,
                (string) $tripCalculationLink->id(),
                $user,
                $request,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        if (($scopeForm->isSubmitted() && $scopeForm->isValid())
            || ($destinationForm->isSubmitted() && $destinationForm->isValid())
            || ($landingForm->isSubmitted() && $landingForm->isValid())
        ) {
            return $this->redirectToRoute('carbon_footprint_trip_calculator');
        }

        return $this->render('@carbon_footprint/account_trip_calculator/index.html.twig', [
            'link' => $tripCalculationLink,
            'scopeForm' => $scopeForm->createView(),
            'destinationForm' => $destinationForm->createView(),
            'landingForm' => $landingForm->createView(),
        ]);
    }
}
