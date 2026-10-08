<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Search;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Search\SearchCategory;
use App\Shared\Application\Search\SearchProvider;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;

class AccountSearchProvider implements SearchProvider
{
    public function __construct(
        private readonly AccountRepository $repository,
        private readonly AccountAuthorizer $authorizer,
    ) {}

    public function category(): SearchCategory
    {
        return new SearchCategory('accounts', 'Cuentas', 'solar:buildings-3-line-duotone', 140);
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Account(AccountId::generate()), Action::List, $user);
    }

    public function search(SearchTerm $term, User $user, int $limit): array
    {
        if (!$this->isAvailableTo($user)) {
            return [];
        }

        return array_map(
            fn(Account $account): SearchResult => new SearchResult(
                title: $account->name(),
                subtitle: null,
                route: 'admin_accounts_edit',
                routeParameters: ['accountId' => (string) $account->id()],
            ),
            $this->repository->findByFilter(new AccountFilter(name: (string) $term, limit: $limit)),
        );
    }
}
