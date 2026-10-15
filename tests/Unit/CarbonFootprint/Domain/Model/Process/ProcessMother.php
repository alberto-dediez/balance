<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Unit;

class ProcessMother
{
    public static function registeredProcess(): Process
    {
        return self::fromName('process-name');
    }

    public static function fromName(string $name): Process
    {
        return self::from(
            name: $name,
            id: (string) ProcessId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Process
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Piece,
    ): Process {
        $process = new Process(
            id: new ProcessId($id),
        );
        $process->register($name, $unit);

        return $process;
    }

    public static function random(): Process
    {
        return self::from(
            name: 'process-' . rand(100, 999),
            id: (string) ProcessId::generate(),
        );
    }
}
