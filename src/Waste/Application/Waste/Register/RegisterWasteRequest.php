<?php

namespace App\Waste\Application\Waste\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterWasteRequest implements Command
{
    public function __construct(
        public string $name = '', 
        public bool $isDangerous = false,
        public string $density = '0',
        public string $color = '#c3d300'
    ) {
        
    }
}