<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;

class TrainingSessionMother
{
    public static function registeredTrainingSession(): TrainingSession
    {
        return self::fromTitle('Training session name');
    }

    public static function fromTitle(string $title): TrainingSession
    {
        return self::from(
            title: $title,
            id: (string) TrainingSessionId::generate(),
            accountId: 'account-id',
        );
    }

    public static function fromTitleAndId(string $title, string $id): TrainingSession
    {
        return self::from(
            title: $title,
            id: $id,
            accountId: 'account-id',
        );
    }

    public static function from(
        string $title,
        string $id,
        string $accountId = 'account-id',
        string $topic = 'A training topic',
        ?\DateTimeImmutable $sessionDate = null,
    ): TrainingSession {
        $trainingSession = new TrainingSession(
            id: new TrainingSessionId($id),
        );
        $trainingSession->register(
            sessionDate: $sessionDate ?? new \DateTimeImmutable('NOW'),
            title: $title,
            topic: $topic,
            accountId: new AccountId($accountId),
        );

        return $trainingSession;
    }

    public static function forAccount(string $accountId): TrainingSession
    {
        return self::from(
            title: 'training-session-' . rand(100, 999),
            id: (string) TrainingSessionId::generate(),
            accountId: $accountId,
        );
    }

    public static function random(): TrainingSession
    {
        return self::from(
            title: 'training-session-' . rand(100, 999),
            id: (string) TrainingSessionId::generate(),
        );
    }
}
