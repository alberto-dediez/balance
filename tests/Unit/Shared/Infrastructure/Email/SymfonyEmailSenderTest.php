<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Email;

use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Infrastructure\Email\SymfonyEmailSender;
use App\Tests\Unit\Shared\Domain\Model\Email\EmailSenderTest;

class SymfonyEmailSenderTest extends EmailSenderTest
{
    public function createSUT(): EmailSender
    {
        return $this->getContainer()->get(SymfonyEmailSender::class);
    }
}
