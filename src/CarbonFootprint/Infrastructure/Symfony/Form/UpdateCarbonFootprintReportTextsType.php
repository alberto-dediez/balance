<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @extends AbstractType<array<string, string>> */
class UpdateCarbonFootprintReportTextsType extends AbstractType
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

        $builder->add('save', SubmitType::class, [
            'label' => new TranslatableMessage('Guardar borrador'),
        ]);

        $builder->add('submitForReview', SubmitType::class, [
            'label' => new TranslatableMessage('Solicitar revisión'),
        ]);

        if (true === $options['autoReview']) {
            $builder->add('issue', SubmitType::class, [
                'label' => new TranslatableMessage('Emitir informe'),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'keys' => [],
            'autoReview' => false,
        ]);

        $resolver->setAllowedTypes('keys', 'array');
        $resolver->setAllowedTypes('autoReview', 'bool');
    }
}
