<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\ViewTechnicalManual;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualStorage;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ViewTechnicalManualService
{
    public function __construct(
        private readonly ChangelogEntryRepository $repository,
        private readonly ManualStorage $storage,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewTechnicalManualRequest $request, User $user): ViewTechnicalManualResponse
    {
        $this->authorizer->denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::History, $user);

        $entry = $this->repository->findBySlug($request->slug);

        if (!$entry instanceof ChangelogEntry || !$entry->published) {
            throw new ChangelogEntryNotFoundException($request->slug);
        }

        if (!$entry->hasTechnicalManual() || null === $entry->technicalManualFileName || !$this->storage->exists($entry->technicalManualFileName)) {
            throw new ManualNotFoundException($entry->technicalManualFileName ?? '');
        }

        return new ViewTechnicalManualResponse(
            entry: $entry,
            markdown: $this->storage->readContents($entry->technicalManualFileName),
        );
    }
}
