<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Search;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Search\GlobalSearchRequest;
use App\Shared\Application\Search\GlobalSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/search', name: 'search', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    public function __invoke(
        GlobalSearchService $search,
        LoggedUser $loggedUser,
        #[MapQueryParameter]
        string $term = '',
    ): Response {
        try {
            $searchResponse = $search(
                new GlobalSearchRequest(
                    term: $term,
                ),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $response = $this->render('@shared/search/results.html.twig', [
            'response' => $searchResponse,
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
