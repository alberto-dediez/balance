<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\List;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;

class ListContactRequestsResponse
{
    /** @param array<ContactRequest> $contactRequests */
    public function __construct(public array $contactRequests = [], public int $totalResults = 0) {}
}
