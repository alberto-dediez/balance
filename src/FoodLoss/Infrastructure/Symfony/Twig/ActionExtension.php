<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ActionExtension extends AbstractExtension
{
    /** @var Action[] */
    private array $actions = [];

    public function __construct(private readonly ActionRepository $actionRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('action_name', $this->actionName(...)),
        ];
    }

    public function actionName(ActionId $actionId): ?Action
    {
        if (!isset($this->actions[(string) $actionId])) {
            $action = $this->actionRepository->find($actionId);

            if ($action instanceof Action) {
                $this->actions[(string) $actionId] = $action;
            }
        }

        return $this->actions[(string) $actionId] ?? null;
    }
}
