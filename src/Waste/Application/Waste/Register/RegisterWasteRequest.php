<?php

namespace App\Waste\Application\Waste\Register;

class RegisterWasteRequest
{
    public function __construct(
        public string $name = '', 
        public bool $isDangerous = false,
        public string $density = '0',
        public string $color = '#c3d300'
    ) {
        
    }
}