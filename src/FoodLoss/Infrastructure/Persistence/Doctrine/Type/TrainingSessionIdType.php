<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TrainingSessionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'training_session_id';
    }

    public function getClassName(): string
    {
        return TrainingSessionId::class;
    }
}
