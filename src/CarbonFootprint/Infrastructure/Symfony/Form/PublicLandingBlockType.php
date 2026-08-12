<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PublicLanding\Update\PublicLandingBlockData;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class PublicLandingBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', MediaPickerType::class, [
                'label' => new TranslatableMessage('Imagen'),
                'required' => false,
                'types' => [MediaType::Image],
                'visibility' => MediaVisibility::Public,
            ])
            ->add('title', TextType::class, [
                'label' => new TranslatableMessage('Título'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('text', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Texto'),
                'required' => false,
                'empty_data' => '',
                'height' => '220px',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PublicLandingBlockData::class,
        ]);
    }
}
