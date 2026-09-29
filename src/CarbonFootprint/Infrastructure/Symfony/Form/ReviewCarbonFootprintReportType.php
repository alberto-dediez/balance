<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @extends AbstractType<array<string, string>> */
class ReviewCarbonFootprintReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<CarbonFootprintReportSectionKey> $keys */
        $keys = $options['keys'];

        foreach ($keys as $key) {
            $required = !$key->isOptional();

            $builder->add($key->value, MarkdownEditorType::class, [
                'label' => new TranslatableMessage($key->label()),
                'height' => '320px',
                'required' => $required,
                'empty_data' => '',
                'constraints' => $required ? [new NotBlank()] : [],
            ]);
        }

        $builder
            ->add('comment', TextareaType::class, [
                'label' => new TranslatableMessage('Comentario para el cliente'),
                'required' => false,
                'empty_data' => '',
                'help' => new TranslatableMessage('Obligatorio si rechazas el informe.'),
            ])
            ->add('reject', SubmitType::class, [
                'label' => new TranslatableMessage('Rechazar'),
            ])
            ->add('approve', SubmitType::class, [
                'label' => new TranslatableMessage('Aprobar y emitir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'keys' => [],
        ]);

        $resolver->setAllowedTypes('keys', 'array');
    }
}
