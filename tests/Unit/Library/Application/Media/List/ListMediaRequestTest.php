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

    public function testAFolderWithItsSubfoldersRestrictsTheListToAllOfThem(): void
    {
        $folderId = FolderId::generate();
        $subfolderId = FolderId::generate();

        $request = new ListMediaRequest();
        $request->folder = ListMediaRequest::WITH_SUBFOLDERS_PREFIX . $folderId;

        $request->applyFolderChoice([$subfolderId]);

        $this->assertCount(2, $request->folderIds);
        $this->assertTrue($request->folderIds[0]->equals($folderId));
        $this->assertTrue($request->folderIds[1]->equals($subfolderId));
        $this->assertNull($request->withoutFolder);
    }

    public function testTheSubfoldersAreIgnoredForAFolderWithoutPrefix(): void
    {
        $folderId = FolderId::generate();

        $request = new ListMediaRequest();
        $request->folder = (string) $folderId;

        $request->applyFolderChoice([FolderId::generate()]);

        $this->assertCount(1, $request->folderIds);
        $this->assertTrue($request->folderIds[0]->equals($folderId));
    }

    public function testChosenFolderIdAndIncludesSubfolders(): void
    {
        $folderId = FolderId::generate();
        $subfolderId = FolderId::generate();

        $request = new ListMediaRequest();

        $this->assertNull($request->chosenFolderId());
        $this->assertFalse($request->includesSubfolders());

        $request->folder = ListMediaRequest::ALL_FOLDERS;

        $this->assertNull($request->chosenFolderId());
        $this->assertFalse($request->includesSubfolders());

        $request->folder = (string) $folderId;

        $this->assertSame((string) $folderId, (string) $request->chosenFolderId());
        $this->assertFalse($request->includesSubfolders());

        $request->folder = (string) $subfolderId;

        $this->assertSame((string) $subfolderId, (string) $request->chosenFolderId());
        $this->assertFalse($request->includesSubfolders());

        $request->folder = ListMediaRequest::WITH_SUBFOLDERS_PREFIX . $folderId;

        $this->assertSame((string) $folderId, (string) $request->chosenFolderId());
        $this->assertTrue($request->includesSubfolders());
    }
}
