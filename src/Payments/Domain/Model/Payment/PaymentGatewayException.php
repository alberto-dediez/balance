<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

class PaymentGatewayException extends \RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 502, $previous);
    }
}
