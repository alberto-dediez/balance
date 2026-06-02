<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

trait GetAlertConfigurationFromIdTrait
{
    private function getAlertConfigurationFromId(string $alertConfigurationId, AlertConfigurationRepository $repository): AlertConfiguration
    {
        $config = $repository->find(new AlertConfigurationId($alertConfigurationId));

        if (null == $config) {
            throw $this->createNotFoundException('Configuración de alerta no encontrada');
        }

        return $config;
    }
}
