<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Autocomplete;

use App\Security\Application\User\Autocomplete\AutocompleteUserRequest;
use App\Security\Application\User\Autocomplete\AutocompleteUserService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class AutocompleteUserServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = UserRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());

        $request = new AutocompleteUserRequest(
            query: '',
        );

        $result = $sut($request, $user);

        $this->assertCount(4, $result->elements);
    }

    private function createSUT(InMemoryUserRepository $repository): AutocompleteUserService
    {
        return new AutocompleteUserService($repository, new UserAuthorizer());
    }
}
