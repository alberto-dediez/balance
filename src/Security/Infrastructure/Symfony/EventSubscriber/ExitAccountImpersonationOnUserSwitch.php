<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\EventSubscriber;

use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

#[AsEventListener]
class ExitAccountImpersonationOnUserSwitch
{
    public function __construct(private readonly ImpersonatedAccount $impersonatedAccount) {}

    public function __invoke(SwitchUserEvent $event): void
    {
        $this->impersonatedAccount->exit();
    }
}
