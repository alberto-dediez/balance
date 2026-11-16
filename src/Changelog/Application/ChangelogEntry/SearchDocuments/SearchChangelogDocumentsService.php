<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\SearchDocuments;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ManualStorage;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class SearchChangelogDocumentsService
{
    public const int MIN_TEXT_LENGTH = 3;
    public const int EXCERPT_CONTEXT = 150;

    public function __construct(
        private readonly ChangelogEntryRepository $repository,
        private readonly ManualStorage $storage,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(SearchChangelogDocumentsRequest $request, User $user): SearchChangelogDocumentsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::List, $user);

        $text = trim($request->text);

        if (mb_strlen($text) < self::MIN_TEXT_LENGTH) {
            return new SearchChangelogDocumentsResponse();
        }

        $filter = new ChangelogEntryFilter();
        $filter->limit = max(1, $this->repository->totalResultsByFilter($filter));

        $includeTechnicalManuals = $this->authorizer->canDoThat(ChangelogEntry::placeholder(), Action::History, $user);

        $matches = [];

        foreach ($this->repository->findByFilter($filter) as $entry) {
            $excerpt = $this->excerpt($entry->body, $text);

            if (null !== $excerpt) {
                $matches[] = new ChangelogDocumentMatch($entry, ChangelogDocumentMatch::SOURCE_ENTRY, $excerpt);
            }

            if (!$includeTechnicalManuals || null === $entry->technicalManualFileName || !$this->storage->exists($entry->technicalManualFileName)) {
                continue;
            }

            $excerpt = $this->excerpt($this->storage->readContents($entry->technicalManualFileName), $text);

            if (null !== $excerpt) {
                $matches[] = new ChangelogDocumentMatch($entry, ChangelogDocumentMatch::SOURCE_TECHNICAL_MANUAL, $excerpt);
            }
        }

        return new SearchChangelogDocumentsResponse(array_slice($matches, 0, max(0, $request->limit)), count($matches));
    }

    private function excerpt(string $contents, string $text): ?string
    {
        $position = mb_stripos($contents, $text);

        if (false === $position) {
            return null;
        }

        $start = max(0, $position - self::EXCERPT_CONTEXT);
        $length = mb_strlen($text) + 2 * self::EXCERPT_CONTEXT;

        return trim(mb_substr($contents, $start, $length));
    }
}
