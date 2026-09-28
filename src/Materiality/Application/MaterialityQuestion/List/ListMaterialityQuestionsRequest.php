<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\List;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Shared\Application\CommandBus\Command;

class ListMaterialityQuestionsRequest extends MaterialityQuestionFilter implements Command {}
