<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\Model\Folder\FolderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class MediaFilter
{
    use Filter;

    public ?bool $withoutAccount = null;

    public ?bool $includeShared = null;

    public ?bool $withoutFolder = null;

    /**
     * @param list<MediaType>  $types         restricts the results to the given media types
     * @param ?MediaVisibility $visibility    restricts the results to the files reaching at
     *                                        least that audience
     * @param list<FolderId>   $folderIds     restricts the results to the files of those folders
     * @param list<FolderId>   $nameFolderIds folders whose files also match the name criterion
     */
    public function __construct(
        public ?string $name = null,
        public ?MediaType $type = null,
        public ?AccountId $accountId = null,
        public array $types = [],
        public ?MediaVisibility $visibility = null,
        ?bool $withoutAccount = null,
        ?bool $includeShared = null,
        int $limit = 24,
        public array $folderIds = [],
        ?bool $withoutFolder = null,
        public array $nameFolderIds = [],
    ) {
        $this->withoutAccount = $withoutAccount;
        $this->includeShared = $includeShared;
        $this->withoutFolder = $withoutFolder;
        $this->limit = $limit;
        $this->orderBy = 'registeredAt';
        $this->orderWay = 'DESC';
    }
}
