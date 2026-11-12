<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Symfony\Kernel;
use App\Shared\Infrastructure\Symfony\Maintenance\MaintenanceGate;

require_once dirname(__DIR__) . '/src/Shared/Infrastructure/Symfony/Maintenance/MaintenanceGate.php';

MaintenanceGate::guard(dirname(__DIR__));

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
