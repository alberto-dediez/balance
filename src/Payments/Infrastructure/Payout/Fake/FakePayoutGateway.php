<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Payout\Fake;

use App\Payments\Domain\Model\Payout\PayoutGateway;
use App\Payments\Domain\Model\Payout\PayoutInstruction;
use App\Payments\Domain\Model\Payout\PayoutReference;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class FakePayoutGateway implements PayoutGateway
{
    /** @var list<PayoutInstruction> */
    private array $sent = [];

    public function name(): string
    {
        return 'fake';
    }

    public function send(PayoutInstruction $instruction): PayoutReference
    {
        $this->sent[] = $instruction;

        return new PayoutReference(gateway: $this->name(), externalId: 'fake_' . $instruction->number);
    }

    /** @return list<PayoutInstruction> */
    public function sent(): array
    {
        return $this->sent;
    }
}
