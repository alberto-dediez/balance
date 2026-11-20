<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\List;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListFoldersService
{
    public function __construct(
        private readonly FolderRepository $repository,
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function __invoke(ListFoldersRequest $request, User $user): ListFoldersResponse
    {
        $folder = new Folder(id: FolderId::generate(), accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($folder, Action::List, $user);

        return new ListFoldersResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
