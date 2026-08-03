<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\List;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Media\MediaType;
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

    private function createSUT(InMemoryMediaRepository $repository): ListMediaService
    {
        return new ListMediaService($repository, MediaAuthorizerMother::create());
    }
}
