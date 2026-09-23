<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;

class CarbonFootprintReportTextsMother
{
    public static function forEvent(bool $withCompensation = true): CarbonFootprintReportTexts
    {
        $sections = [
            CarbonFootprintReportSectionKey::ExecutiveSummary->value => self::paragraphs('El evento **Feria Verde 2026** ha generado un total de emisiones acorde a su tamaño y tipología.', 'Las principales fuentes han sido los desplazamientos de los asistentes y los materiales empleados en el montaje.'),
            CarbonFootprintReportSectionKey::Methodology->value => self::paragraphs('El cálculo sigue el **GHG Protocol** y agrupa las actividades por fases: materiales, montaje, celebración y desmontaje.', "## Fuentes consideradas\n\n- Materiales y merchandising\n- Movilidad y alojamiento\n- Consumo eléctrico y gestión de residuos"),
            CarbonFootprintReportSectionKey::EmissionFactorSources->value => self::paragraphs('Los factores de emisión proceden del grupo de coeficientes **MITECO 2026** y de las bases de datos oficiales publicadas para el año de cálculo.', 'Cuando no existe un factor específico se aplica el factor genérico de la categoría.'),
            CarbonFootprintReportSectionKey::AnalysisAndRecommendations->value => self::paragraphs('La movilidad concentra la mayor parte de las emisiones, seguida de los materiales.', "### Recomendaciones\n\n- Fomentar el transporte colectivo de los asistentes\n- Reutilizar la estructura de los stands\n- Contratar electricidad con garantía de origen renovable"),
        ];

        if ($withCompensation) {
            $sections[CarbonFootprintReportSectionKey::Compensation->value] = self::paragraphs('La organización ha compensado parte de las emisiones mediante proyectos de reforestación certificados.', 'La compensación complementa, pero no sustituye, las medidas de reducción.');
        }

        return new CarbonFootprintReportTexts($sections);
    }

    public static function forOrganization(): CarbonFootprintReportTexts
    {
        return new CarbonFootprintReportTexts([
            CarbonFootprintReportSectionKey::Purpose->value => self::paragraphs('Este informe recoge la huella de carbono de la organización como **indicador ambiental** de su actividad.', 'Conocer las emisiones es el primer paso para reducirlas y para inscribir la huella en el registro del MITECO.'),
            CarbonFootprintReportSectionKey::RegulatoryContext->value => self::paragraphs('El cálculo se apoya en el **GHG Protocol** y sus cinco principios: integridad, consistencia, transparencia, relevancia y precisión.', "## Alcances\n\n- Alcance 1: emisiones directas de instalaciones, vehículos y fugas de refrigerantes\n- Alcance 2: emisiones indirectas por consumo eléctrico"),
            CarbonFootprintReportSectionKey::SustainableDevelopmentGoals->value => self::paragraphs('La medición de la huella contribuye al **ODS 13** (acción por el clima), al ODS 7 (energía asequible y no contaminante) y al ODS 12 (producción y consumo responsables).', 'La organización puede vincular sus objetivos de reducción con estos ODS.'),
            CarbonFootprintReportSectionKey::Conclusion->value => self::paragraphs('Las instalaciones y la electricidad concentran la mayor parte de las emisiones del periodo.', "### Líneas de reducción\n\n- Mejorar la eficiencia energética de las instalaciones\n- Contratar electricidad con garantía de origen\n- Revisar el mantenimiento de los equipos de refrigeración"),
        ]);
    }

    private static function paragraphs(string ...$paragraphs): string
    {
        return implode("\n\n", $paragraphs);
    }
}
