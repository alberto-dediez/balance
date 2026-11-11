<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\ApiToken;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\List\ListApiTokensRequest;
use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\ListApiTokensType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile/api-tokens', name: 'profile_api_tokens')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListApiTokensService $listApiTokens,
        ApiTokenPolicy $policy,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        if (!$policy->userCanHaveTokens($user)) {
            throw $this->createAccessDeniedException();
        }

        try {
            [$filter, $form] = $this->filter($request, fn(): ListApiTokensRequest => new ListApiTokensRequest(userId: $user->id()), ListApiTokensType::class);

            assert($filter instanceof ListApiTokensRequest);

            $filter->userId = $user->id();

            $response = $listApiTokens($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/api-tokens/list.html.twig', [
            'apiTokens' => $response->apiTokens,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
