<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Track;

class TrackEmailEventResponse
{
    public function __construct(public bool $tracked) {}
}
