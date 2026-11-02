<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\User;

final class SectorBenchmarkAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof SectorBenchmark;
    }
}
