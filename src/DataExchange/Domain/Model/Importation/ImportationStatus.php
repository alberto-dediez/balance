<?php

namespace App\DataExchange\Domain\Model\Importation;

enum ImportationStatus: string
{
    case InProgress = "InProgress";

    case Executed = "Executed";
}
