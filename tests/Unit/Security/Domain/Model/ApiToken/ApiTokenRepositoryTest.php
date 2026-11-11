<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\ApiToken\ApiTokenFilter;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\ApiToken\ApiTokenStatus;
use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ApiTokenRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $apiToken = ApiTokenMother::active(UserId::generate());

        $this->assertNull($repository->find($apiToken->id()));

        $repository->add($apiToken);

        $recovered = $repository->find($apiToken->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($apiToken->id(), $recovered->id());
    }

    public function testFindByTokenHash(): void
    {
        $repository = $this->createSUT();

        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, UserId::generate());
        $repository->add($apiToken);
        $repository->add(ApiTokenMother::active(UserId::generate()));

        $found = $repository->findByTokenHash(ApiTokenSecret::hashFor($secret->plainValue()));

        $this->assertNotNull($found);
        $this->assertEquals($apiToken->id(), $found->id());
        $this->assertNull($repository->findByTokenHash(ApiTokenSecret::hashFor('hyd_unknown')));
    }

    public function testFindByFilterByUser(): void
    {
        $repository = $this->createSUT();

        $userId = UserId::generate();
        $repository->add(ApiTokenMother::active($userId));
        $repository->add(ApiTokenMother::revoked($userId));
        $repository->add(ApiTokenMother::active(UserId::generate()));

        $now = new \DateTimeImmutable('NOW');

        $this->assertCount(3, $repository->findByFilter(new ApiTokenFilter(), $now));
        $this->assertCount(2, $repository->findByFilter(new ApiTokenFilter(userId: $userId), $now));
        $this->assertSame(2, $repository->totalResultsByFilter(new ApiTokenFilter(userId: $userId, limit: 1), $now));
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $userId = UserId::generate();
        $active = ApiTokenMother::active($userId);
        $expired = ApiTokenMother::expired($userId);
        $revoked = ApiTokenMother::revoked($userId);
        $repository->add($active);
        $repository->add($expired);
        $repository->add($revoked);

        $now = new \DateTimeImmutable('NOW');

        $activeResults = $repository->findByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Active), $now);
        $this->assertCount(1, $activeResults);
        $this->assertEquals($active->id(), $activeResults[0]->id());

        $expiredResults = $repository->findByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Expired), $now);
        $this->assertCount(1, $expiredResults);
        $this->assertEquals($expired->id(), $expiredResults[0]->id());

        $revokedResults = $repository->findByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Revoked), $now);
        $this->assertCount(1, $revokedResults);
        $this->assertEquals($revoked->id(), $revokedResults[0]->id());
    }

    public function testCountActiveByUserIdExcludesRevokedAndExpired(): void
    {
        $repository = $this->createSUT();

        $userId = UserId::generate();
        $repository->add(ApiTokenMother::active($userId));
        $repository->add(ApiTokenMother::active($userId));
        $repository->add(ApiTokenMother::expired($userId));
        $repository->add(ApiTokenMother::revoked($userId));
        $repository->add(ApiTokenMother::active(UserId::generate()));

        $now = new \DateTimeImmutable('NOW');

        $this->assertSame(2, $repository->countActiveByUserId($userId, $now));
        $this->assertCount(2, $repository->findActiveByUserId($userId, $now));
    }

    public function testDefaultOrderIsRegisteredAtDescending(): void
    {
        $repository = $this->createSUT();

        $userId = UserId::generate();
        $older = ApiTokenMother::from(ApiTokenId::generate(), $userId, null, 'older', ApiTokenSecret::generate(), null, new \DateTimeImmutable('-2 days'));
        $newer = ApiTokenMother::from(ApiTokenId::generate(), $userId, null, 'newer', ApiTokenSecret::generate(), null, new \DateTimeImmutable('-1 day'));
        $repository->add($older);
        $repository->add($newer);

        $results = $repository->findByFilter(new ApiTokenFilter(userId: $userId), new \DateTimeImmutable('NOW'));

        $this->assertCount(2, $results);
        $this->assertEquals($newer->id(), $results[0]->id());
        $this->assertEquals($older->id(), $results[1]->id());
    }

    abstract protected function createSUT(): ApiTokenRepository;
}
