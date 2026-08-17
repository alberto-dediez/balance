<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Autocomplete;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

class AutocompleteAccountService
{
    public function __construct(private readonly AccountRepository $repository, private readonly AccountAuthorizer $authorizer) {}

    public function __invoke(AutocompleteAccountRequest $request, User $user): AutocompleteAccountResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Account(AccountId::generate()), Action::Autocomplete, $user);

        $filter = new AccountFilter(
            name: $request->query,
            active: true,
        );

        $accounts = $this->repository->findByFilter($filter);

        return new AutocompleteAccountResponse($accounts);
    }
}
