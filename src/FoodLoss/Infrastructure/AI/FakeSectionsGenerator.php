<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSection;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSectionId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Domain\Model\PreventionPlan\SectionsGenerator;
use App\Shared\Domain\Model\JobProgress\JobProgressTracker;

class FakeSectionsGenerator implements SectionsGenerator
{
    public function generate(PreventionPlanVersion $version): void
    {
        $fakeSections = [
            ['Introducción', 'Este plan de prevención describe las medidas adoptadas para reducir las pérdidas alimentarias en las propiedades asociadas.'],
            ['Diagnóstico', 'Se han identificado los puntos críticos de pérdida alimentaria en cada etapa de la cadena de suministro.'],
            ['Acciones Propuestas', 'A continuación se detallan las acciones concretas acordadas para reducir las pérdidas en cada punto crítico identificado.'],
        ];

        foreach ($fakeSections as [$title, $content]) {
            $version->addSection(
                new PreventionPlanSection(
                    id: PreventionPlanSectionId::generate(),
                    preventionPlanVersion: $version,
                    title: $title,
                    content: $content,
                ),
            );

            JobProgressTracker::instance()->advance($title);
        }
    }
}
