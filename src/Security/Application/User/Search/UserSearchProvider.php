<?php

declare(strict_types=1);

namespace App\Security\Application\User\Search;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\Search\SearchCategory;
use App\Shared\Application\Search\SearchProvider;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;

class UserSearchProvider implements SearchProvider
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly AccountRepository $accountRepository,
        private readonly UserAuthorizer $authorizer,
        private readonly ?CurrentAccountIdProvider $currentAccountIdProvider = null,
    ) {}

    public function category(): SearchCategory
    {
        return new SearchCategory('users', 'Usuarios', 'solar:users-group-rounded-line-duotone', 150);
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->isAdministrator($user) || $this->ownAccountId($user) instanceof AccountId;
    }

    public function search(SearchTerm $term, User $user, int $limit): array
    {
        if ($this->isAdministrator($user)) {
            return $this->results(new UserFilter(username: (string) $term, limit: $limit), 'admin_users_edit');
        }

        $ownAccountId = $this->ownAccountId($user);

        if (!$ownAccountId instanceof AccountId) {
            return [];
        }

        $account = $this->accountRepository->find($ownAccountId);

        if (!$account instanceof Account) {
            return [];
        }

        return $this->results(new UserFilter(username: (string) $term, account: $account, limit: $limit), 'users_edit');
    }

    /** @return list<SearchResult> */
    private function results(UserFilter $filter, string $route): array
    {
        return array_map(
            fn(User $found): SearchResult => new SearchResult(
                title: $found->username(),
                subtitle: $found->account()?->name(),
                route: $route,
                routeParameters: ['userId' => (string) $found->id()],
            ),
            $this->repository->findByFilter($filter),
        );
    }

    private function isAdministrator(User $user): bool
    {
        return $this->authorizer->canDoThat(new User(
            id: UserId::generate(),
        ), Action::List, $user);
    }

    private function ownAccountId(User $user): ?AccountId
    {
        $currentAccountId = $this->currentAccountIdProvider?->currentAccountId() ?? $user->accountId();

        if (!$currentAccountId instanceof AccountId) {
            return null;
        }

        $canList = $this->authorizer->canDoThat(new User(
            id: UserId::generate(),
            accountId: $currentAccountId,
        ), Action::List, $user);

        return $canList ? $currentAccountId : null;
    }
}
