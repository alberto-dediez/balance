<?php

namespace App\Security\Infrastructure\Symfony\Form;

class EditUser
{
    public function __construct(public array $roles)
    {
        
    }
}