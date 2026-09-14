<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\List;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListChangelogEntriesService
{
    public function __construct(
        private readonly ChangelogEntryRepository $repository,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(ListChangelogEntriesRequest $request, User $user): ListChangelogEntriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::List, $user);

        $request->onlyPublished = true;

        return new ListChangelogEntriesResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
