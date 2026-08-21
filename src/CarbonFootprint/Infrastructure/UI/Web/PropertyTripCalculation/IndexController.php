<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\UI\Web\PublicLanding\PublicLandingForm;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip-calculations/{propertyId}', name: 'carbon_footprint_property_trip_calculations', methods: ['GET', 'POST'])]
class IndexController extends AbstractController
{
    use GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId,
        PropertyRepository $propertyRepository,
        PublicLandingForm $publicLandingForm,
        TripCalculationScopeForm $tripCalculationScopeForm,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $property);

        try {
            $landingForm = $publicLandingForm->handle(
                PublicLandingOwnerType::Property,
                $propertyId,
                $loggedUser(),
                $request,
            );

            $scopeForm = null !== $property->accountId()
                ? $tripCalculationScopeForm->handle(new PropertyId($propertyId), $loggedUser(), $request)
                : null;
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if (($landingForm->isSubmitted() && $landingForm->isValid())
            || (null !== $scopeForm && $scopeForm->isSubmitted() && $scopeForm->isValid())
        ) {
            return $this->redirectToRoute('carbon_footprint_property_trip_calculations', ['propertyId' => $propertyId]);
        }

        return $this->render('@carbon_footprint/property_trip_calculations/index.html.twig', [
            'property' => $property,
            'landingForm' => $landingForm->createView(),
            'scopeForm' => $scopeForm?->createView(),
        ]);
    }
}
