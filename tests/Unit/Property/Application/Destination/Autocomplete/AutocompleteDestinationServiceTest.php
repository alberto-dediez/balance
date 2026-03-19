<?php

namespace App\Tests\Unit\Property\Application\Destination\Autocomplete;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationRequest;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationService;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class AutocompleteDestinationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $sut = $this->createSUT($repository);

        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());

        $request = new AutocompleteDestinationRequest(
            query: ''
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof AutocompleteDestinationResponse);
        $this->assertCount(4, $result->elements);
    }

    private function createSUT(InMemoryDestinationRepository $repository): AutocompleteDestinationService
    {
        return new AutocompleteDestinationService($repository, new DestinationAuthorizer());
    }
}