<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Symfony\Form;

use App\Changelog\Application\Announcement\Update\UpdateAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<UpdateAnnouncementRequest>
 */
class UpdateAnnouncementType extends AbstractType
{
    use AddsManualFieldTrait;

    public function __construct(private readonly MediaRepository $mediaRepository) {}

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
        ;

        $this->addManualField($builder, $this->mediaRepository);

        $builder
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateAnnouncementRequest::class,
        ]);
    }
}
