<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySector\List;

use App\Materiality\Application\MaterialitySector\List\ListMaterialitySectorsRequest;
use App\Materiality\Application\MaterialitySector\List\ListMaterialitySectorsService;
use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMaterialitySectorsServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();
        $repository->add(MaterialitySectorMother::fromName('Hoteles'));
        $repository->add(MaterialitySectorMother::fromName('Eventos'));
        $repository->add(MaterialitySectorMother::fromName('Restauración'));

        $result = $this->createSUT($repository)(
            new ListMaterialitySectorsRequest(limit: 2),
            UserMother::adminUser(),
        );

        $this->assertCount(2, $result->materialitySectors);
        $this->assertSame(3, $result->totalResults);
    }

    public function testOwnerCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(MaterialitySectorRepositoryMother::inMemory())(
            new ListMaterialitySectorsRequest(),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(MaterialitySectorRepository $repository): ListMaterialitySectorsService
    {
        return new ListMaterialitySectorsService(
            materialitySectorRepository: $repository,
            authorizer: new MaterialitySectorAuthorizer(),
        );
    }
}
