<?php

namespace App\Property\Infrastructure\UI\Web\Destination;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationRequest;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationService;

#[Route('/destinations/autocomplete', name: 'destinations_autocomplete')]
class AutocompleteController extends AbstractController
{
    public function __invoke(
        #[MapQueryParameter] string $query, 
        AutocompleteDestinationService $autocompleteDestination, 
        LoggedUser $loggedUser
    ): Response {
        $response = $autocompleteDestination(
            new AutocompleteDestinationRequest(
                query: $query
            ),
            $loggedUser()
        );

        return $this->json(
            $response->elements
        );
    }
}
