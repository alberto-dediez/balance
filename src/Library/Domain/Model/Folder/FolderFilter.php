<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class FolderFilter
{
    use Filter;

    public ?bool $withoutAccount = null;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        ?bool $withoutAccount = null,
        int $limit = 10,
        public ?FolderId $parentId = null,
    ) {
        $this->withoutAccount = $withoutAccount;
        $this->limit = $limit;
        $this->orderBy = 'name';
        $this->orderWay = 'ASC';
    }
}
