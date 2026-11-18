<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\PlanStep;
use App\Shared\Application\Assistant\ToolArguments;

class PlanStepVerifier
{
    public function verify(PlanStep $step, ActionPlan $plan, string $tool, ToolArguments $arguments): ?string
    {
        if ($tool !== $step->tool()) {
            return sprintf('La llamada a «%s» no corresponde al paso %d, que es «%s».', $tool, $step->position(), $step->tool());
        }

        $literals = $step->arguments();
        $deferred = $step->deferred();
        $received = $arguments->all();

        $expectedNames = array_keys($literals + array_fill_keys(array_keys($deferred), []));
        $receivedNames = array_keys($received);
        sort($expectedNames);
        sort($receivedNames);

        if ($expectedNames !== $receivedNames) {
            return sprintf('Los argumentos de la llamada no son los del paso %d: se esperaban %s.', $step->position(), implode(', ', $expectedNames));
        }

        foreach ($literals as $name => $values) {
            if ($received[$name] !== $values) {
                return sprintf('El argumento «%s» no coincide con el del paso %d aprobado.', $name, $step->position());
            }
        }

        foreach ($deferred as $name => $fromStep) {
            $outputs = $plan->stepAt($fromStep)?->outputValues() ?? [];

            foreach ($received[$name] as $value) {
                if (!in_array($value, $outputs, true)) {
                    return sprintf('El argumento «%s» debe ser un resultado del paso %d.', $name, $fromStep);
                }
            }
        }

        return null;
    }
}
