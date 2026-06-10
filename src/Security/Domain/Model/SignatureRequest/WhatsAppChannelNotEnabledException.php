<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class WhatsAppChannelNotEnabledException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El canal WhatsApp no está habilitado en esta instalación.', 400);
    }
}
