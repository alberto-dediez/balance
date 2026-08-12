<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\Project\Register\RegisterProjectRequest;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectStatusType;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectTypeType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('description', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('location', DestinationIdType::class, [
                'label' => new TranslatableMessage('Localización'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('gallery', MediaPickerType::class, [
                'label' => new TranslatableMessage('Galería'),
                'required' => false,
                'multiple' => true,
                'empty_data' => [],
                'visibility' => MediaVisibility::Public,
            ])
            ->add('projectType', ProjectTypeType::class, [
                'label' => new TranslatableMessage('Tipo de proyecto'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('projectStatus', ProjectStatusType::class, [
                'label' => new TranslatableMessage('Estado del proyecto'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('mitecoId', TextType::class, [
                'label' => new TranslatableMessage('Código Registro Miteco'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('costPerTonne', NumberType::class, [
                'label' => new TranslatableMessage('Coste por tonelada (€)'),
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\PositiveOrZero(),
                ],
            ])
            ->add('pricePerTonne', NumberType::class, [
                'label' => new TranslatableMessage('Precio por tonelada (€)'),
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterProjectRequest::class,
        ]);
    }
}
