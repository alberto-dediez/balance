<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\User\Autocomplete\AutocompleteUserRequest;
use App\Security\Application\User\Autocomplete\AutocompleteUserService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users/autocomplete', name: 'admin_users_autocomplete')]
class AutocompleteController extends AbstractController
{
    public function __invoke(
        #[MapQueryParameter]
        string $query,
        AutocompleteUserService $autocompleteUser,
        LoggedUser $loggedUser,
    ): Response {
        $response = $autocompleteUser(
            new AutocompleteUserRequest(
                query: $query,
            ),
            $loggedUser(),
        );

        return $this->json(
            $response->elements,
        );
    }
}
