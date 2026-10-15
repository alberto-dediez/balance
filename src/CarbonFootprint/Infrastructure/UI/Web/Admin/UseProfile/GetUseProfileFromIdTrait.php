<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetUseProfileFromIdTrait
{
    private function getUseProfileFromId(string $useProfileId, UseProfileRepository $repository): UseProfile
    {
        $useProfile = $repository->find(new UseProfileId($useProfileId));

        if (null == $useProfile) {
            throw $this->createNotFoundException((string) new TranslatableMessage('Perfil de uso no encontrado'));
        }

        return $useProfile;
    }
}
