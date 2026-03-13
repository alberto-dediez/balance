<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\Locale\Locale;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ActionRepositoryTest extends KernelTestCase
{
    public function testAddActionToRepository()
    {   
        $repository = $this->createSUT();

        $action = ActionMother::registeredAction();

        $this->assertNull($repository->find($action->id()));

        $repository->add($action);

        $recoverAction = $repository->find($action->id());

        $this->assertNotNull($recoverAction);
        $this->assertEquals($action->id(), $recoverAction->id());
    }

    public function testRemoveActionToRepository()
    {   
        $repository = $this->createSUT();

        $action = ActionMother::registeredAction();

        $repository->add($action);

        $recoverAction = $repository->find($action->id());

        $this->assertNotNull($recoverAction);
        $this->assertEquals($action->id(), $recoverAction->id());

        $repository->remove($action);

        $this->assertNull($repository->find($action->id()));
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::fromDescription('Descripción de la acción'));

        $filter = new ActionFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ActionFilter(
            description: 'Descripción de la acción'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::fromDescription('Descripción de la acción'));

        $filter = new ActionFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ActionFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $action = ActionMother::registeredAction();

        $repository->add($action);

        $recoverAction = $repository->find($action->id());

        $this->assertNotNull($recoverAction);

        $recoverAction->remove();

        $repository->add($action);

        $this->assertNull($repository->find($action->id()));
    }

    public function testFindRecoverWithTranslation()
    {
        $repository = $this->createSUT(Locale::English);

        $action = ActionMother::registeredAction();

        $this->assertNull($repository->find($action->id()));

        $action->translate('translated-description', Locale::English);

        $repository->add($action);

        $recoverAction = $repository->find($action->id());

        $this->assertNotNull($recoverAction);
        $this->assertEquals($action->id(), $recoverAction->id());

        $this->assertEquals('translated-description', $recoverAction->description());
    }

    public function testFindByFilterWithDescriptionTranslated()
    {
        $repository = $this->createSUT(Locale::English);

        $action = ActionMother::registeredAction();

        $this->assertNull($repository->find($action->id()));

        $action->translate('translated-description', Locale::English);

        $repository->add($action);

        $recoverActions = $repository->findByFilter(new ActionFilter(description: 'translated-description'));

        $this->assertCount(1, $recoverActions);
        $this->assertEquals($action->id(), $recoverActions[0]->id());

        $this->assertEquals('translated-description', $recoverActions[0]->description());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): ActionRepository;
}
