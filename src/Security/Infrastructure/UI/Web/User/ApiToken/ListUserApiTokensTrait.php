<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User\ApiToken;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\List\ListApiTokensRequest;
use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\ListApiTokensType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ListUserApiTokensTrait
{
    use FilterTrait;
    use GetUserFromIdTrait;

    protected function listUserApiTokens(
        string $userId,
        UserRepository $userRepository,
        ListApiTokensService $listApiTokens,
        LoggedUser $loggedUser,
        Request $request,
        string $template,
        string $revokeRoute,
    ): Response {
        $user = $this->getUserFromId($userId, $userRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $user);

        try {
            [$filter, $form] = $this->filter($request, fn(): ListApiTokensRequest => new ListApiTokensRequest(userId: $user->id()), ListApiTokensType::class);

            assert($filter instanceof ListApiTokensRequest);

            $filter->userId = $user->id();

            $response = $listApiTokens($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render($template, [
            'user' => $user,
            'apiTokens' => $response->apiTokens,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'revokeRoute' => $revokeRoute,
        ]);
    }
}
