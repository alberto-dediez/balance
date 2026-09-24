<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class UserRepositoryTest extends KernelTestCase
{
    public function testAddUserToRepository(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $this->assertNull($repository->find($user->id()));

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);
        $this->assertEquals($user->id(), $recoverUser->id());
    }

    public function testAddUserOfAnAccountKeepsItsPreferences(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, AccountId::generate());
        $user->changePreferredLocale(Locale::English);

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);
        $this->assertEquals(Locale::English, $recoverUser->preferredLocale());
    }

    public function testAddUserOfAnAccountKeepsItsTwoFactorState(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::from(
            userId: UserId::generate(),
            username: 'two-factor@dediez.software',
            accountId: AccountId::generate(),
            twoFactorMethod: TwoFactorMethod::Totp,
            totpSecret: 'stored-secret',
        );
        $user->requestTotpEnrolment('pending-secret');

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);
        $this->assertEquals(TwoFactorMethod::Totp, $recoverUser->twoFactorMethod());
        $this->assertEquals('stored-secret', $recoverUser->totpSecret());
        $this->assertEquals('pending-secret', $recoverUser->pendingTotpSecret());
        $this->assertTrue($recoverUser->twoFactorChallenge()->isPending());
    }

    public function testRemoveUserToRepository(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);
        $this->assertEquals($user->id(), $recoverUser->id());

        $repository->remove($user);

        $this->assertNull($repository->find($user->id()));
    }

    public function testFindByUsername(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $this->assertNull($repository->findByUsername($user->username()));

        $repository->add($user);

        $recoverUser = $repository->findByUsername($user->username());

        $this->assertNotNull($recoverUser);
        $this->assertEquals($user->id(), $recoverUser->id());
    }

    public function testFindByRecoverToken(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $this->assertNull($repository->findByUsername($user->username()));

        $user->initPasswordRecovery();

        $repository->add($user);

        $token = $user->recoverToken();

        $this->assertNotNull($token);

        $recoverUser = $repository->findByRecoverToken($token);

        $this->assertNotNull($recoverUser);
        $this->assertEquals($user->id(), $recoverUser->id());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::fromUsername('alberto'));

        $filter = new UserFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new UserFilter(
            username: 'alberto',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithRoleExcludesProvidersByDefault(): void
    {
        $repository = $this->createSUT();

        $repository->add(UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin));
        $repository->add(UserMother::fromUsernameAndRole('user@dediez.software', Role::User));
        $repository->add(UserMother::fromUsernameAndRole('provider@dediez.software', Role::Provider));

        $admins = $repository->findByFilter(new UserFilter(role: Role::Admin));

        $this->assertCount(1, $admins);
        $this->assertEquals('admin@dediez.software', $admins[0]->username());

        $this->assertCount(2, $repository->findByFilter(new UserFilter()));
        $this->assertCount(3, $repository->findByFilter(new UserFilter(excludeRole: null)));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::fromUsername('alberto'));

        $filter = new UserFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new UserFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverDeletedUser(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);

        $recoverUser->remove();

        $repository->add($user);

        $this->assertNull($repository->find($user->id()));
    }

    public function testRegisterSameUsernameAfterUserIsDeleted(): void
    {
        $repository = $this->createSUT();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $recoverUser = $repository->find($user->id());

        $this->assertNotNull($recoverUser);

        $recoverUser->remove();

        $repository->add($user);

        $newUser = UserMother::fromUsername($user->username());

        $repository->add($newUser);

        $recoverUser = $repository->findByUsername($newUser->username());
        $this->assertNotNull($recoverUser);
        $this->assertTrue($recoverUser->id()->equals($newUser->id()));
    }

    public function testRegisterSameUsernameTwiceThrowException(): void
    {
        $this->expectException(DuplicatedUsernameException::class);

        $repository = $this->createSUT();

        $user = UserMother::random();

        $repository->add($user);

        $newUser = UserMother::fromUsername($user->username());

        $repository->add($newUser);
    }

    public function testTotalResultsByFilterIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());

        $filter = new UserFilter(
            limit: 2,
        );

        $this->assertCount(2, $repository->findByFilter($filter));

        $this->assertEquals(6, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): UserRepository;
}
