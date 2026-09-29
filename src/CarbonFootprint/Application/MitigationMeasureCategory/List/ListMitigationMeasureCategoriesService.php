<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\List;

use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMitigationMeasureCategoriesService
{
    public function __construct(private MitigationMeasureCategoryRepository $repository, private MitigationMeasureCategoryAuthorizer $authorizer) {}

    public function __invoke(ListMitigationMeasureCategoriesRequest $request, User $user): ListMitigationMeasureCategoriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MitigationMeasureCategory(id: MitigationMeasureCategoryId::generate()), SecurityAction::List, $user);

        $mitigationMeasureCategories = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMitigationMeasureCategoriesResponse($mitigationMeasureCategories, $totalResults);
    }
}
