<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User;

use App\Security\Application\User\UserFinder;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class UserFinderTest extends TestCase
{
    public function testFindsTheUser(): void
    {
        $repository = UserRepositoryMother::inMemory();
        $user = UserMother::registeredUser();
        $repository->add($user);
        $sut = new UserFinder($repository);

        $this->assertSame($user, $sut->find($user->id()));
    }

    public function testMemorizesAMissingIdUntilItIsReset(): void
    {
        $repository = UserRepositoryMother::inMemory();
        $user = UserMother::registeredUser();
        $sut = new UserFinder($repository);

        $this->assertNull($sut->find($user->id()));

        $repository->add($user);

        $this->assertNull($sut->find($user->id()));

        $sut->reset();

        $this->assertSame($user, $sut->find($user->id()));
    }
}
