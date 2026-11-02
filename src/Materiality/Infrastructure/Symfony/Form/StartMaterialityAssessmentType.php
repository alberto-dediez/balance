<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialitySectorChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<array<string, mixed>> */
class StartMaterialityAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['sectors_available']) {
            $builder->add('sectorId', MaterialitySectorChoiceType::class, [
                'label' => new TranslatableMessage('Sector'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin sector específico'),
                'help' => new TranslatableMessage('Determina qué preguntas se aplican. Sin sector solo se formulan las preguntas comunes a todos los sectores.'),
                'data' => $options['default_sector_id'],
            ]);
        }

        if (null !== $options['previous_year']) {
            $builder->add('copyPreviousAnswers', CheckboxType::class, [
                'label' => new TranslatableMessage('Partir de la evaluación de {year}', ['{year}' => $options['previous_year']]),
                'required' => false,
                'data' => true,
            ]);
        }

        $builder->add('start', SubmitType::class, [
            'label' => new TranslatableMessage('Comenzar cuestionario'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'previous_year' => null,
            'default_sector_id' => null,
            'sectors_available' => true,
        ]);

        $resolver->setAllowedTypes('previous_year', ['null', 'int']);
        $resolver->setAllowedTypes('default_sector_id', ['null', MaterialitySectorId::class]);
        $resolver->setAllowedTypes('sectors_available', 'bool');
    }
}
