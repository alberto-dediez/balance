<?php

namespace App\Security\Application\User\Autocomplete;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;

class AutocompleteUserService
{
    public function __construct(private readonly UserRepository $repository, private readonly UserAuthorizer $authorizer)
    {

    }

    public function __invoke(AutocompleteUserRequest $request, User $user): AutocompleteUserResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new User(
            id: UserId::generate()
        ), Action::Autocomplete, $user);

        $filter = new UserFilter(
            username: $request->query
        );

        $destinations = $this->repository->findByFilter($filter);

        return new AutocompleteUserResponse($destinations);
    }
}