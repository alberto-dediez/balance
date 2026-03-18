<?php

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
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
            username: 'alberto'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
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
            limit: 0
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

    abstract protected function createSUT(): UserRepository;
}
