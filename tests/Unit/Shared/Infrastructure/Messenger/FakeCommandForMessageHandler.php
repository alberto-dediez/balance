<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Shared\Application\CommandBus\Command;

class FakeCommandForMessageHandler implements Command {}
