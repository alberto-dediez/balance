<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Symfony\Form;

use App\Changelog\Application\Announcement\Register\RegisterAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<RegisterAnnouncementRequest>
 */
class RegisterAnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EnumType::class, [
                'class' => ChangelogCategory::class,
                'label' => new TranslatableMessage('Categoría'),
                'required' => true,
                'choice_label' => fn(ChangelogCategory $category): string => $category->name,
                'choice_translation_domain' => 'changelogCategory',
                'constraints' => [new NotNull()],
            ])
            ->add('date', DateType::class, [
                'label' => new TranslatableMessage('Fecha'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new NotNull()],
            ])
            ->add('version', TextType::class, [
                'label' => new TranslatableMessage('Versión'),
                'required' => false,
                'constraints' => [new Length(max: 50)],
            ])
            ->add('title', TextType::class, [
                'label' => new TranslatableMessage('Título'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => new TranslatableMessage('Resumen'),
                'required' => true,
                'empty_data' => '',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ])
            ->add('body', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Contenido'),
                'required' => false,
                'empty_data' => '',
                'hide_link' => false,
                'height' => '350px',
            ])
            ->add('youtubeUrl', UrlType::class, [
                'label' => new TranslatableMessage('Enlace a un vídeo de YouTube'),
                'required' => false,
                'default_protocol' => 'https',
                'constraints' => [
                    new Callback(static function (?string $value, ExecutionContextInterface $context): void {
                        if (null === $value || '' === trim($value) || YouTubeVideo::isValidUrl($value)) {
                            return;
                        }

                        $context->buildViolation('No es un enlace válido de YouTube')
                            ->setTranslationDomain('messages')
                            ->addViolation()
                        ;
                    }),
                ],
            ])
            ->add('manual', FileType::class, [
                'label' => new TranslatableMessage('Manual (PDF)'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '20M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => new TranslatableMessage('El manual debe ser un PDF'),
                    ]),
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
            'data_class' => RegisterAnnouncementRequest::class,
        ]);
    }
}
