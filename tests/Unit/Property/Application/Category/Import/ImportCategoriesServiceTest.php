<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Category\Import;

use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Application\Category\Import\CategoryRequest;
use App\Property\Application\Category\Import\ImportCategoriesRequest;
use App\Property\Application\Category\Import\ImportCategoriesService;
use App\Property\Application\Category\Register\RegisterCategoryResponse;
use App\Property\Application\Category\Register\RegisterCategoryService;
use App\Property\Application\Category\Update\UpdateCategoryService;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCategoryRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportCategoriesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = CategoryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $star = CategoryMother::fromName('1 Estrella');
        $twoStars = CategoryMother::fromName('2 Estrellas');
        $key = CategoryMother::fromName('1 llave');

        $repository->add($star);
        $repository->add($key);
        $repository->add($twoStars);
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());

        $request = new ImportCategoriesRequest(
            [
                new CategoryRequest(
                    name: '1 estrella',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '1234'),
                    createIfNotExists: false,
                ),
                new CategoryRequest(
                    name: '1 LLAVE',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '234'),
                    createIfNotExists: false,
                ),
                new CategoryRequest(
                    name: '5 Estrellas Gran Lujo',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '34'),
                    createIfNotExists: false,
                ),
                new CategoryRequest(
                    name: 'Hostal',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '4'),
                    createIfNotExists: true,
                ),
            ],
        );

        $result = $sut($request, $user);

        $this->assertCount(4, $result->categories);
        $this->assertEquals(3, $result->matched);

        $this->assertNotNull($result->categories[0]->categoryId);
        $this->assertNotNull($result->categories[1]->categoryId);
        $this->assertNull($result->categories[2]->categoryId);
        $this->assertNotNull($result->categories[3]->categoryId);

        $this->assertEquals('1234', $star->externalId()->bioscore());
    }

    private function createSUT(InMemoryCategoryRepository $repository): ImportCategoriesService
    {
        $registerService = $this->createMock(RegisterCategoryService::class);
        $registerService->method('__invoke')->willReturn(new RegisterCategoryResponse(CategoryId::generate(), ''));

        /** @var RegisterCategoryService&MockObject $registerService */
        $authorizer = new CategoryAuthorizer();

        $updateService = new UpdateCategoryService(
            $repository,
            $authorizer,
        );

        return new ImportCategoriesService(
            repository: $repository,
            registerCategory: $registerService,
            updateCategory: $updateService,
            authorizer: $authorizer,
        );
    }
}
