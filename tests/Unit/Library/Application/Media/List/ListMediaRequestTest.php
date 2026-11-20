<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\List;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Domain\Model\Folder\FolderId;
use PHPUnit\Framework\TestCase;

class ListMediaRequestTest extends TestCase
{
    public function testWithoutAChoiceOnlyTheMediaWithoutFolderIsListed(): void
    {
        $request = new ListMediaRequest();
        $request->folderIds = [FolderId::generate()];

        $request->applyFolderChoice();

        $this->assertSame([], $request->folderIds);
        $this->assertTrue($request->withoutFolder);
    }

    public function testAllTheFoldersRemovesTheFolderRestriction(): void
    {
        $request = new ListMediaRequest();
        $request->folder = ListMediaRequest::ALL_FOLDERS;

        $request->applyFolderChoice();

        $this->assertSame([], $request->folderIds);
        $this->assertNull($request->withoutFolder);
    }

    public function testAFolderRestrictsTheListToIt(): void
    {
        $folderId = FolderId::generate();

        $request = new ListMediaRequest();
        $request->folder = (string) $folderId;

        $request->applyFolderChoice();

        $this->assertCount(1, $request->folderIds);
        $this->assertTrue($request->folderIds[0]->equals($folderId));
        $this->assertNull($request->withoutFolder);
    }
}
