<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Shared\Domain\Model\Locale\Locale;

enum StandardMaterialityQuestion: string
{
    case Magnitude = 'magnitude';

    case Influence = 'influence';

    case Risk = 'risk';

    case StakeholderInterest = 'stakeholder_interest';

    case Outsourcing = 'outsourcing';

    public function criterion(): MaterialityCriterion
    {
        return MaterialityCriterion::from($this->value);
    }

    public function answerScale(): MaterialityAnswerScale
    {
        return self::Outsourcing === $this ? MaterialityAnswerScale::YesNo : MaterialityAnswerScale::Level;
    }

    public function weight(): int
    {
        return self::Magnitude === $this ? 2 : 1;
    }

    public function position(): int
    {
        return match ($this) {
            self::Magnitude => 1,
            self::Influence => 2,
            self::Risk => 3,
            self::StakeholderInterest => 4,
            self::Outsourcing => 5,
        };
    }

    public function text(Locale $locale): ?string
    {
        return match ($locale) {
            Locale::Spanish => match ($this) {
                self::Magnitude => '¿Qué magnitud estimada tienen las emisiones de esta categoría respecto al total de la organización?',
                self::Influence => '¿Qué capacidad tiene la organización de influir o controlar las emisiones de esta categoría?',
                self::Risk => '¿Qué riesgo (regulatorio, financiero, reputacional o de cadena de suministro) asocia la organización a esta categoría?',
                self::StakeholderInterest => '¿Qué interés muestran los grupos de interés (clientes, inversores, administraciones, empleados) por las emisiones de esta categoría?',
                self::Outsourcing => '¿La actividad de esta categoría está externalizada, cuando antes se realizaba internamente o es habitual realizarla internamente en el sector?',
            },
            Locale::English => match ($this) {
                self::Magnitude => 'How large are the estimated emissions of this category compared with the organisation\'s total?',
                self::Influence => 'To what extent can the organisation influence or control the emissions of this category?',
                self::Risk => 'How much risk (regulatory, financial, reputational or supply-chain) does the organisation associate with this category?',
                self::StakeholderInterest => 'How much interest do stakeholders (customers, investors, regulators, employees) show in the emissions of this category?',
                self::Outsourcing => 'Is the activity of this category outsourced, when it was previously performed in-house or is typically performed in-house in the sector?',
            },
            default => null,
        };
    }

    public function help(Locale $locale): ?string
    {
        return match ($locale) {
            Locale::Spanish => match ($this) {
                self::Magnitude => "- **Alto**: previsiblemente una de las mayores fuentes.\n- **Bajo**: marginal frente al resto.",
                self::Influence => "- **Alto**: decisiones propias (compras, contratos, políticas) cambian las emisiones.\n- **Bajo**: sin palanca real.",
                self::Risk => "- **Alto**: riesgos identificados o previsibles a corto plazo.\n- **Bajo**: sin riesgos relevantes.",
                self::StakeholderInterest => "- **Alto**: peticiones o requisitos explícitos.\n- **Bajo**: ningún interés manifestado.",
                self::Outsourcing => '**Sí**: la externalización desplaza emisiones de los alcances 1 y 2 al alcance 3 y la categoría gana relevancia.',
            },
            Locale::English => match ($this) {
                self::Magnitude => "- **High**: foreseeably one of the largest sources.\n- **Low**: marginal compared with the rest.",
                self::Influence => "- **High**: the organisation's own decisions (purchasing, contracts, policies) change the emissions.\n- **Low**: no real leverage.",
                self::Risk => "- **High**: risks identified or foreseeable in the short term.\n- **Low**: no relevant risks.",
                self::StakeholderInterest => "- **High**: explicit requests or requirements.\n- **Low**: no interest expressed.",
                self::Outsourcing => '**Yes**: outsourcing shifts emissions from scopes 1 and 2 to scope 3 and the category becomes more relevant.',
            },
            default => null,
        };
    }
}
