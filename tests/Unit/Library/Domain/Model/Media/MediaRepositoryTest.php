<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MediaRepositoryTest extends KernelTestCase
{
    public function testAddMediaToRepository(): void
    {
        $repository = $this->createSUT();

        $media = MediaMother::uploadedMedia();

        $this->assertNull($repository->find($media->id()));

        $repository->add($media);

        $recoverMedia = $repository->find($media->id());

        $this->assertNotNull($recoverMedia);
        $this->assertEquals($media->id(), $recoverMedia->id());
    }

    public function testRemoveMediaFromRepository(): void
    {
        $repository = $this->createSUT();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $this->assertNotNull($repository->find($media->id()));

        $repository->remove($media);

        $this->assertNull($repository->find($media->id()));
    }

    public function testFindDoesNotRecoverRemovedMedia(): void
    {
        $repository = $this->createSUT();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $recoverMedia = $repository->find($media->id());

        $this->assertNotNull($recoverMedia);

        $recoverMedia->remove();

        $repository->add($recoverMedia);

        $this->assertNull($repository->find($media->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());
        $repository->add(MediaMother::fromName('minotour-logo.png'));

        $resultUnfiltered = $repository->findByFilter(new MediaFilter());

        $this->assertCount(4, $resultUnfiltered);

        $result = $repository->findByFilter(new MediaFilter(name: 'minotour'));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByType(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());
        $repository->add(MediaMother::document());

        $images = $repository->findByFilter(new MediaFilter(type: MediaType::Image));

        $this->assertCount(2, $images);

        $documents = $repository->findByFilter(new MediaFilter(type: MediaType::Document));

        $this->assertCount(1, $documents);
    }

    public function testFindByFilterByTypeList(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::document());

        $result = $repository->findByFilter(new MediaFilter(types: [MediaType::Document, MediaType::Video]));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByVisibilityRecoversTheMediaReachingAtLeastThatAudience(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::fromNameAndVisibility('private.png', MediaVisibility::Account));
        $repository->add(MediaMother::fromNameAndVisibility('manual.png', MediaVisibility::System));
        $repository->add(MediaMother::fromNameAndVisibility('terms.png', MediaVisibility::Public));

        $result = $repository->findByFilter(new MediaFilter(visibility: MediaVisibility::System));

        $names = array_map(static fn($media): string => $media->name(), $result);

        $this->assertCount(2, $result);
        $this->assertContains('manual.png', $names);
        $this->assertContains('terms.png', $names);
        $this->assertNotContains('private.png', $names);
    }

    public function testFindByFilterByAccountOnlyRecoversTheMediaOfThatAccount(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $anotherAccountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', $anotherAccountId));
        $repository->add(MediaMother::fromName('without-account.png'));

        $result = $repository->findByFilter(new MediaFilter(accountId: $accountId));

        $this->assertCount(1, $result);
        $this->assertEquals('own.png', $result[0]->name());
    }

    public function testFindByFilterWithoutAccount(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::fromNameAndAccount('own.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('without-account.png'));

        $result = $repository->findByFilter(new MediaFilter(withoutAccount: true));

        $this->assertCount(1, $result);
        $this->assertEquals('without-account.png', $result[0]->name());
    }

    public function testFindByFilterIncludingTheSharedMedia(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $anotherAccountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', $anotherAccountId));
        $repository->add(MediaMother::sharedMedia('manual.pdf', $anotherAccountId));
        $repository->add(MediaMother::sharedMedia('terms.pdf', $anotherAccountId));

        $repository->add(MediaMother::fromNameAndVisibility('avatar.png', MediaVisibility::System, $anotherAccountId));

        $result = $repository->findByFilter(new MediaFilter(accountId: $accountId, includeShared: true));

        $names = array_map(static fn($media): string => $media->name(), $result);

        $this->assertCount(3, $result);
        $this->assertContains('own.png', $names);
        $this->assertContains('manual.pdf', $names);
        $this->assertContains('terms.pdf', $names);
        $this->assertNotContains('avatar.png', $names);
    }

    public function testFindByFilterWithoutAccountIncludingTheSharedMedia(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::fromNameAndAccount('own.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('without-account.png'));
        $repository->add(MediaMother::sharedMedia('manual.pdf', AccountId::generate()));

        $result = $repository->findByFilter(new MediaFilter(withoutAccount: true, includeShared: true));

        $this->assertCount(2, $result);
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());

        $filter = new MediaFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertEquals(3, $repository->totalResultsByFilter($filter));
    }

    public function testPaginatingOneByOneRecoversEveryMediaRegisteredAtTheSameTime(): void
    {
        $repository = $this->createSUT();

        $expected = [];

        for ($i = 0; $i < 5; ++$i) {
            $media = MediaMother::random();
            $repository->add($media);
            $expected[] = (string) $media->id();
        }

        $recovered = [];
        $filter = new MediaFilter(limit: 1);

        for ($page = 0; $page < 5; ++$page) {
            $filter->page = $page;

            foreach ($repository->findByFilter($filter) as $media) {
                $recovered[] = (string) $media->id();
            }
        }

        sort($expected);
        sort($recovered);

        $this->assertSame($expected, $recovered);
    }

    public function testFindRecoversAMediaInAFolder(): void
    {
        $repository = $this->createSUT();

        $folderId = FolderId::generate();

        $media = MediaMother::inFolder('logo.png', $folderId);

        $repository->add($media);

        $recovered = $repository->find($media->id());

        $this->assertNotNull($recovered);
        $this->assertNotNull($recovered->folderId());
        $this->assertTrue($recovered->folderId()->equals($folderId));
    }

    public function testFindByFilterWithoutFolderCriteriaRecoversEveryMedia(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::inFolder('contract.png', FolderId::generate()));
        $repository->add(MediaMother::fromName('logo.png'));

        $this->assertCount(2, $repository->findByFilter(new MediaFilter()));
    }

    public function testFindByFilterByFoldersOnlyRecoversTheMediaOfThoseFolders(): void
    {
        $repository = $this->createSUT();

        $contracts = FolderId::generate();
        $invoices = FolderId::generate();

        $repository->add(MediaMother::inFolder('contract.png', $contracts));
        $repository->add(MediaMother::inFolder('invoice.png', $invoices));
        $repository->add(MediaMother::inFolder('other.png', FolderId::generate()));
        $repository->add(MediaMother::fromName('logo.png'));

        $this->assertSame(['contract.png'], $this->namesOf($repository->findByFilter(new MediaFilter(folderIds: [$contracts]))));

        $names = $this->namesOf($repository->findByFilter(new MediaFilter(folderIds: [$contracts, $invoices])));

        sort($names);

        $this->assertSame(['contract.png', 'invoice.png'], $names);
    }

    public function testFindByFilterWithoutFolderInTheAccountExcludesTheMediaInFolders(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(MediaMother::inFolder('contract.png', FolderId::generate(), $accountId));
        $repository->add(MediaMother::fromNameAndAccount('logo.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', AccountId::generate()));

        $result = $repository->findByFilter(new MediaFilter(accountId: $accountId, withoutFolder: true));

        $this->assertSame(['logo.png'], $this->namesOf($result));
    }

    public function testFindByFilterWithoutFolderIncludingTheSharedMedia(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $anotherAccountId = AccountId::generate();

        $foreignShared = MediaMother::sharedMedia('manual.png', $anotherAccountId);
        $foreignShared->moveToFolder(FolderId::generate());

        $ownShared = MediaMother::sharedMedia('own-shared.png', $accountId);
        $ownShared->moveToFolder(FolderId::generate());

        $repository->add($foreignShared);
        $repository->add($ownShared);
        $repository->add(MediaMother::fromNameAndAccount('logo.png', $accountId));
        $repository->add(MediaMother::inFolder('contract.png', FolderId::generate(), $accountId));

        $names = $this->namesOf($repository->findByFilter(new MediaFilter(accountId: $accountId, includeShared: true, withoutFolder: true)));

        sort($names);

        $this->assertSame(['logo.png', 'manual.png'], $names);
    }

    public function testFindByFilterWithoutFolderAndWithoutAccount(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::inFolder('contract.png', FolderId::generate()));
        $repository->add(MediaMother::fromName('logo.png'));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', AccountId::generate()));

        $result = $repository->findByFilter(new MediaFilter(withoutAccount: true, withoutFolder: true));

        $this->assertSame(['logo.png'], $this->namesOf($result));
    }

    public function testFindByFilterWithoutFolderAndWithoutAccountScope(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::inFolder('contract.png', FolderId::generate(), AccountId::generate()));
        $repository->add(MediaMother::inFolder('invoice.png', FolderId::generate()));
        $repository->add(MediaMother::fromNameAndAccount('logo.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('banner.png'));

        $names = $this->namesOf($repository->findByFilter(new MediaFilter(withoutFolder: true)));

        sort($names);

        $this->assertSame(['banner.png', 'logo.png'], $names);
    }

    public function testFindByFilterByNameAlsoRecoversTheMediaOfTheMatchingFolders(): void
    {
        $repository = $this->createSUT();

        $contracts = FolderId::generate();

        $repository->add(MediaMother::inFolder('signed.png', $contracts));
        $repository->add(MediaMother::fromName('contract-template.png'));
        $repository->add(MediaMother::inFolder('other.png', FolderId::generate()));

        $names = $this->namesOf($repository->findByFilter(new MediaFilter(name: 'contr', nameFolderIds: [$contracts])));

        sort($names);

        $this->assertSame(['contract-template.png', 'signed.png'], $names);

        $this->assertSame(['contract-template.png'], $this->namesOf($repository->findByFilter(new MediaFilter(name: 'contr'))));
    }

    public function testNameFolderIdsWithoutNameDoNotRestrictTheResults(): void
    {
        $repository = $this->createSUT();

        $contracts = FolderId::generate();

        $repository->add(MediaMother::inFolder('signed.png', $contracts));
        $repository->add(MediaMother::fromName('logo.png'));

        $this->assertCount(2, $repository->findByFilter(new MediaFilter(nameFolderIds: [$contracts])));
    }

    /**
     * @param list<Media> $media
     *
     * @return list<string>
     */
    private function namesOf(array $media): array
    {
        return array_map(static fn(Media $element): string => $element->name(), $media);
    }

    abstract protected function createSUT(): MediaRepository;
}
