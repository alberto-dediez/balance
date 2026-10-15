<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Process;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetProcessFromIdTrait
{
    private function getProcessFromId(string $processId, ProcessRepository $repository): Process
    {
        $process = $repository->find(new ProcessId($processId));

        if (null == $process) {
            throw $this->createNotFoundException((string) new TranslatableMessage('Proceso no encontrado'));
        }

        return $process;
    }
}
