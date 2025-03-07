<?php

namespace App\Waste\Application\Waste\Register;

class RegisterWasteRequest
{
    public function __construct(public string $name = '')
    {
        
    }
}