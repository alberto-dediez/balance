<?php

namespace App\Tests\Unit\Security\Application\User\Autocomplete;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Application\User\UserAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\User\Autocomplete\AutocompleteUserRequest;
use App\Security\Application\User\Autocomplete\AutocompleteUserService;
use App\Security\Application\User\Autocomplete\AutocompleteUserResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;

class AutocompleteUserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = UserRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());

        $request = new AutocompleteUserRequest(
            query: ''
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof AutocompleteUserResponse);
        $this->assertCount(4, $result->elements);
    }

    private function createSUT($repository)
    {
        return new AutocompleteUserService($repository, new UserAuthorizer());
    }
}