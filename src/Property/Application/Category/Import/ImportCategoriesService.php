<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Import;

use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Application\Category\Register\RegisterCategoryRequest;
use App\Property\Application\Category\Register\RegisterCategoryService;
use App\Property\Application\Category\Update\UpdateCategoryRequest;
use App\Property\Application\Category\Update\UpdateCategoryService;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ImportCategoriesService
{
    public function __construct(
        private readonly CategoryRepository $repository,
        private readonly RegisterCategoryService $registerCategory,
        private readonly UpdateCategoryService $updateCategory,
        private readonly CategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportCategoriesRequest $request, User $user): ImportCategoriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Category(
            id: CategoryId::generate(),
        ), Action::Admin, $user);

        $categoriesMatch = $this->matchCategories($request->categories, $user);

        $totalResults = array_sum(
            array_map(fn(CategoryResponse $response) => $response->categoryId instanceof CategoryId ? 1 : 0, $categoriesMatch),
        );

        return new ImportCategoriesResponse($categoriesMatch, $totalResults);
    }

    private function matchCategories(array $categoryRequests, User $user): array
    {
        if (0 === count($categoryRequests)) {
            return [];
        }

        $result = [];

        foreach ($categoryRequests as $request) {
            $categoryMatch = $this->repository->findBySourceAndExternalId(
                source: $request->source,
                externalId: $request->externalId,
            );

            if (null == $categoryMatch) {
                $categoryMatch = $this->repository->findByName($request->name);

                if ($categoryMatch instanceof Category) {
                    $this->updateCategory->__invoke(new UpdateCategoryRequest(
                        $categoryMatch->id(),
                        $categoryMatch->name(),
                        $categoryMatch->externalId()->combine(
                            $request->externalId,
                        ),
                    ), $user);
                }
            }

            if (null == $categoryMatch && $request->createIfNotExists) {
                $registerResponse = $this->registerCategory->__invoke(new RegisterCategoryRequest(
                    name: $request->name,
                    externalId: $request->externalId,
                ), $user);

                $categoryMatch = new Category($registerResponse->categoryId);
            }

            $result[] = new CategoryResponse(
                name: $request->name,
                externalId: $request->externalId,
                createIfNotExists: $request->createIfNotExists,
                categoryId: $categoryMatch?->id(),
            );
        }

        return $result;
    }
}
