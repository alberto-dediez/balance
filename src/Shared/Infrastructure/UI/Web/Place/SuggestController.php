<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Place;

use App\Shared\Application\Place\Suggest\SuggestPlacesRequest;
use App\Shared\Application\Place\Suggest\SuggestPlacesService;
use App\Shared\Domain\Model\Geo\PlaceKind;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/places/suggest', name: 'places_suggest')]
class SuggestController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $placeSuggestLimiter,
    ) {}

    public function __invoke(
        #[MapQueryParameter]
        string $query,
        SuggestPlacesService $suggestPlaces,
        Request $request,
        #[MapQueryParameter]
        ?string $kind = null,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->placeSuggestLimiter->create($this->getUser()?->getUserIdentifier() ?? $request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $response = $suggestPlaces(new SuggestPlacesRequest(
            query: $query,
            kind: PlaceKind::tryFrom((string) $kind) ?? PlaceKind::Any,
        ));

        return $this->json(PlaceSuggestionsPresenter::toChoices($response->suggestions));
    }
}
