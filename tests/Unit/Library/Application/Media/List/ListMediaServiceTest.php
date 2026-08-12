<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\List;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMediaServiceTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = new InMemoryMediaRepository();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::random());
        $repository->add(MediaMother::document());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListMediaRequest(), UserMother::adminUser());

        $this->assertCount(3, $result->media);
        $this->assertEquals(3, $result->totalResults);
    }

    public function testFilteringByType(): void
    {
        $repository = new InMemoryMediaRepository();

        $repository->add(MediaMother::random());
        $repository->add(MediaMother::document());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListMediaRequest(type: MediaType::Document), UserMother::adminUser());

        $this->assertCount(1, $result->media);
    }

    public function testUsersOnlyBrowseTheirOwnAccountLibrary(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('without-account.png'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId);

        $result = $sut(new ListMediaRequest(), $user);

        $this->assertCount(1, $result->media);
        $this->assertEquals('own.png', $result->media[0]->name());
    }

    public function testAdminsAlsoOnlyBrowseTheirOwnAccountLibrary(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', AccountId::generate()));

        $sut = $this->createSUT($repository);

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, $accountId);

        $result = $sut(new ListMediaRequest(), $admin);

        $this->assertCount(1, $result->media);
        $this->assertEquals('own.png', $result->media[0]->name());
    }

    public function testUsersWithoutAccountOnlyBrowseTheMediaWithoutAccount(): void
    {
        $repository = new InMemoryMediaRepository();

        $repository->add(MediaMother::fromNameAndAccount('own.png', AccountId::generate()));
        $repository->add(MediaMother::fromName('without-account.png'));

        $sut = $this->createSUT($repository);

        $result = $sut(new ListMediaRequest(), UserMother::adminUser());

        $this->assertCount(1, $result->media);
        $this->assertEquals('without-account.png', $result->media[0]->name());
    }

    public function testSharedMediaIsNotBrowsedByDefault(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, AccountId::generate()));
        $repository->add(MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public, AccountId::generate()));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId);

        $result = $sut(new ListMediaRequest(), $user);

        $this->assertCount(1, $result->media);
        $this->assertEquals('own.png', $result->media[0]->name());
    }

    public function testSharedMediaIsBrowsedWhenAskedForThroughTheFilter(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();
        $foreignAccountId = AccountId::generate();

        $repository->add(MediaMother::fromNameAndAccount('own.png', $accountId));
        $repository->add(MediaMother::fromNameAndAccount('foreign.png', $foreignAccountId));
        $repository->add(MediaMother::sharedMedia('manual.pdf', $foreignAccountId));

        $repository->add(MediaMother::fromNameAndVisibility('avatar.png', MediaVisibility::System, $foreignAccountId));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId);

        $result = $sut(new ListMediaRequest(includeShared: true), $user);

        $names = array_map(static fn($media): string => $media->name(), $result->media);

        $this->assertCount(2, $result->media);
        $this->assertContains('own.png', $names);
        $this->assertContains('manual.pdf', $names);
        $this->assertNotContains('avatar.png', $names);
    }

    private function createSUT(InMemoryMediaRepository $repository): ListMediaService
    {
        return new ListMediaService($repository, MediaAuthorizerMother::create());
    }
}
