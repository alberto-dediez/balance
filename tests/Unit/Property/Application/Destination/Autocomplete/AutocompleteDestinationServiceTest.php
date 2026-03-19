<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Destination\Autocomplete;

use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationRequest;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationResponse;
use App\Property\Application\Destination\Autocomplete\AutocompleteDestinationService;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AutocompleteDestinationServiceTest extends TestCase
{
    protected function setUp(): void {}

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
            query: '',
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
