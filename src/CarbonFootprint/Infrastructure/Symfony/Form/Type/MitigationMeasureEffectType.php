<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Application\MitigationMeasure\Update\MitigationMeasureEffectData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<MitigationMeasureEffectData> */
class MitigationMeasureEffectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('source', MitigationSourceType::class, [
                'label' => new TranslatableMessage('Fuente'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('minimumReduction', PercentType::class, [
                'label' => new TranslatableMessage('Reducción mínima'),
                'required' => true,
                'scale' => 2,
                'constraints' => [new Assert\NotNull(), new Assert\Range(min: 0, max: 1)],
            ])
            ->add('maximumReduction', PercentType::class, [
                'label' => new TranslatableMessage('Reducción máxima'),
                'required' => true,
                'scale' => 2,
                'constraints' => [new Assert\NotNull(), new Assert\Range(min: 0, max: 1)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MitigationMeasureEffectData::class,
            'constraints' => [new Assert\Callback([self::class, 'validateRange'])],
        ]);
    }

    public static function validateRange(?MitigationMeasureEffectData $effect, ExecutionContextInterface $context): void
    {
        if (!$effect instanceof MitigationMeasureEffectData || null === $effect->minimumReduction || null === $effect->maximumReduction) {
            return;
        }

        if ($effect->minimumReduction > $effect->maximumReduction) {
            $context->buildViolation('La reducción mínima no puede ser mayor que la máxima')
                ->atPath('maximumReduction')
                ->addViolation()
            ;
        }
    }
}
