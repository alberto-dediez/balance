<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MitigationMeasureChoiceType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<mixed> */
class RegisterMitigationActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $year = $options['year'];

        $builder
            ->add('measure', MitigationMeasureChoiceType::class, [
                'label' => new TranslatableMessage('Medida'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Crea una medida manual o escoge una de nuestras sugerencias'),
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('description', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
                'empty_data' => '',
                'height' => '220px',
                'hide_image' => true,
            ])
            ->add('targetYear', IntegerType::class, [
                'label' => new TranslatableMessage('Año objetivo'),
                'required' => true,
                'data' => $year + 5,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\GreaterThan($year),
                    new Assert\LessThanOrEqual($year + 50),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('year');
        $resolver->setAllowedTypes('year', 'int');
        $resolver->setDefaults([
            'constraints' => [new Assert\Callback([self::class, 'validateName'])],
        ]);
    }

    public static function validateName(mixed $data, ExecutionContextInterface $context): void
    {
        if (!is_array($data) || null !== ($data['measure'] ?? null)) {
            return;
        }

        if ('' === trim((string) ($data['name'] ?? ''))) {
            $context->buildViolation('El nombre es obligatorio si no se escoge una medida sugerida')
                ->atPath('[name]')
                ->addViolation()
            ;
        }
    }
}
