<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\List;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Shared\Application\CommandBus\Command;

class ListMaterialityTopicsRequest extends MaterialityTopicFilter implements Command {}
