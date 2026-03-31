<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;

class ActionMother
{
    public static function registeredAction(): Action
    {
        return self::fromDescription('description');
    }

    public static function fromDescription(string $description): Action
    {
        return self::from(
            description: $description,
            priority: self::generateRandomPriority(),
            id: (string) ActionId::generate(),
        );
    }

    public static function fromPriority(Priority $priority): Action
    {
        return self::from(
            description: 'action-' . rand(100, 999),
            priority: $priority,
            id: (string) ActionId::generate(),
        );
    }

    public static function fromDescriptionAndId(string $description, string $id): Action
    {
        return self::from(
            description: $description,
            priority: self::generateRandomPriority(),
            id: $id,
        );
    }

    public static function fromPriorityAndId(Priority $priority, string $id): Action
    {
        return self::from(
            description: 'action-' . rand(100, 999),
            priority: $priority,
            id: $id,
        );
    }

    public static function from(
        string $description,
        Priority $priority,
        string $id,
    ): Action {
        $action = new Action(
            id: new ActionId($id),
        );
        $action->register($description, $priority);

        return $action;
    }

    public static function random(): Action
    {
        return self::from(
            description: 'action-' . rand(100, 999),
            priority: self::generateRandomPriority(),
            id: (string) ActionId::generate(),
        );
    }

    private static function generateRandomPriority(): Priority
    {
        $cases = Priority::cases();
        $randomCase = $cases[random_int(0, count($cases) - 1)];

        return $randomCase;
    }
}
