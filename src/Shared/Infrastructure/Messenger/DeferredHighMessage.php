<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async_high')]
class DeferredHighMessage extends DeferredMessage {}
