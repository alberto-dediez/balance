<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\DownloadManual;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualStorage;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class DownloadManualService
{
    public function __construct(
        private readonly ChangelogEntryRepository $repository,
        private readonly ManualStorage $storage,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadManualRequest $request, User $user): DownloadManualResponse
    {
        $this->authorizer->denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::View, $user);

        $entry = $this->repository->findBySlug($request->slug);

        if (!$entry instanceof ChangelogEntry || !$entry->published) {
            throw new ChangelogEntryNotFoundException($request->slug);
        }

        if (!$entry->hasManual() || null === $entry->manualFileName || !$this->storage->exists($entry->manualFileName)) {
            throw new ManualNotFoundException($entry->manualFileName ?? '');
        }

        return new DownloadManualResponse(
            stream: $this->storage->read($entry->manualFileName),
            fileName: $entry->manualFileName,
            mimeType: 'application/pdf',
            size: $this->storage->size($entry->manualFileName),
        );
    }
}
