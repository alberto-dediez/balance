<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterTrainingSessionRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $sessionDate = new \DateTimeImmutable('NOW'),
        public string $title = '',
        public string $topic = '',
        public ?AccountId $accountId = null,
    ) {}
}
