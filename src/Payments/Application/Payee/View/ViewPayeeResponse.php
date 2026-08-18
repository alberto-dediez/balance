<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\View;

use App\Payments\Domain\Model\Payee\Payee;

final class ViewPayeeResponse
{
    public function __construct(public readonly ?Payee $payee = null) {}
}
