<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Autocomplete;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

class AutocompleteAccountService
{
    public function __construct(
        private readonly AccountRepository $repository,
        private readonly AccountAuthorizer $authorizer,
        private readonly ImpersonationPolicy $impersonationPolicy = new ImpersonationPolicy(),
    ) {}

    public function __invoke(AutocompleteAccountRequest $request, User $user): AutocompleteAccountResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Account(AccountId::generate()), Action::Autocomplete, $user);

        $filter = $this->impersonationPolicy->impersonableAccountsFilter($user, $request->query);

        if (!$filter instanceof AccountFilter) {
            return new AutocompleteAccountResponse([]);
        }

        return new AutocompleteAccountResponse($this->repository->findByFilter($filter));
    }
}
