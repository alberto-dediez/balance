<?php

namespace App\Security\Application;

use App\Security\Domain\Model\User\User;

interface Authorizer
{
    public function denyUnlessCanDoThat(mixed $subject, Action $action, User $user);
    public function canDoThat(mixed $subject, Action $action, User $user): bool;
    public function supports(mixed $subject): bool;
}