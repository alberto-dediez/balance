<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

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

    /**
     * A field requiring a given audience only offers the files already reaching it, the
     * wider ones included.
     */
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
        $repository->add(MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, $anotherAccountId));
        $repository->add(MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public, $anotherAccountId));

        $result = $repository->findByFilter(new MediaFilter(accountId: $accountId, includeShared: true));

        $names = array_map(static fn($media): string => $media->name(), $result);

        $this->assertCount(3, $result);
        $this->assertContains('own.png', $names);
        $this->assertContains('manual.pdf', $names);
        $this->assertContains('terms.pdf', $names);
    }

    public function testFindByFilterWithoutAccountIncludingTheSharedMedia(): void
    {
        $repository = $this->createSUT();

        $repository->add(MediaMother::fromNameAndAccount('own.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('without-account.png'));
        $repository->add(MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, AccountId::generate()));

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

    abstract protected function createSUT(): MediaRepository;
}
