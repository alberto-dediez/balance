<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\UpdateSections;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Application\PreventionPlan\UpdateSections\SectionData;
use App\FoodLoss\Application\PreventionPlan\UpdateSections\UpdatePreventionPlanSectionsRequest;
use App\FoodLoss\Application\PreventionPlan\UpdateSections\UpdatePreventionPlanSectionsService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdatePreventionPlanSectionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUpdateSections(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::withGeneratedSections('Plan 1', 'plan-1', 'account-id-test');
        $repository->add($preventionPlan);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $version = $preventionPlan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        $this->assertCount(2, $sections);

        $request = new UpdatePreventionPlanSectionsRequest(
            preventionPlanId: $preventionPlan->id(),
            sections: [
                new SectionData(
                    sectionId: $sections[0]->id(),
                    title: 'Título actualizado',
                    content: 'Contenido actualizado',
                ),
                new SectionData(
                    sectionId: $sections[1]->id(),
                    title: 'Segundo título actualizado',
                    content: 'Segundo contenido actualizado',
                ),
            ],
        );

        $result = $this->createSUT($repository)($request, $user);

        $this->assertEquals($preventionPlan->id(), $result->preventionPlanId);

        $persisted = $repository->find($preventionPlan->id());
        $this->assertNotNull($persisted);

        $updatedSections = $persisted->sections()->toArray();
        $this->assertCount(2, $updatedSections);
        $this->assertEquals('Título actualizado', $updatedSections[0]->title());
        $this->assertEquals('Contenido actualizado', $updatedSections[0]->content());
        $this->assertEquals('Segundo título actualizado', $updatedSections[1]->title());
    }

    public function testRemoveSectionWhenNotInRequest(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::withGeneratedSections('Plan 1', 'plan-1', 'account-id-test');
        $repository->add($preventionPlan);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $version = $preventionPlan->currentPlanVersion();
        $sections = $version->sections()->toArray();
        $this->assertCount(2, $sections);

        $request = new UpdatePreventionPlanSectionsRequest(
            preventionPlanId: $preventionPlan->id(),
            sections: [
                new SectionData(
                    sectionId: $sections[0]->id(),
                    title: $sections[0]->title(),
                    content: $sections[0]->content(),
                ),
            ],
        );

        $this->createSUT($repository)($request, $user);

        $persisted = $repository->find($preventionPlan->id());
        $this->assertNotNull($persisted);

        $updatedSections = $persisted->sections()->toArray();
        $this->assertCount(1, $updatedSections);
    }

    public function testAddNewSectionWhenSectionIdIsNull(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::withGeneratedSections('Plan 1', 'plan-1', 'account-id-test');
        $repository->add($preventionPlan);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $version = $preventionPlan->currentPlanVersion();
        $sections = $version->sections()->toArray();
        $this->assertCount(2, $sections);

        $request = new UpdatePreventionPlanSectionsRequest(
            preventionPlanId: $preventionPlan->id(),
            sections: [
                new SectionData(
                    sectionId: $sections[0]->id(),
                    title: $sections[0]->title(),
                    content: $sections[0]->content(),
                ),
                new SectionData(
                    sectionId: $sections[1]->id(),
                    title: $sections[1]->title(),
                    content: $sections[1]->content(),
                ),
                new SectionData(
                    sectionId: null,
                    title: 'Nueva sección',
                    content: 'Contenido nuevo',
                ),
            ],
        );

        $this->createSUT($repository)($request, $user);

        $persisted = $repository->find($preventionPlan->id());
        $this->assertNotNull($persisted);

        $updatedSections = $persisted->sections()->toArray();
        $this->assertCount(3, $updatedSections);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PreventionPlanRepositoryMother::inMemory());

        $result = $sut->isHandled(new UpdatePreventionPlanSectionsRequest(
            preventionPlanId: PreventionPlanMother::registeredPreventionPlan()->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PreventionPlanRepository $repository): UpdatePreventionPlanSectionsService
    {
        return new UpdatePreventionPlanSectionsService(
            preventionPlanRepository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
