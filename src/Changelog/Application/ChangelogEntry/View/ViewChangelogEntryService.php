<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\View;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ViewChangelogEntryService
{
    public function __construct(
        private readonly ChangelogEntryRepository $repository,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewChangelogEntryRequest $request, User $user): ViewChangelogEntryResponse
    {
        $this->authorizer->denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::View, $user);

        $entry = $this->repository->findBySlug($request->slug);

        if (!$entry instanceof ChangelogEntry || !$entry->published) {
            throw new ChangelogEntryNotFoundException($request->slug);
        }

        return new ViewChangelogEntryResponse($entry);
    }
}
