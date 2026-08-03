<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Reusable field that lets any form pick an already existing media from the
 * library or upload a new one without leaving the page.
 *
 * @extends AbstractType<mixed>
 */
class MediaPickerType extends AbstractType
{
    /**
     * A session backed token id on purpose: the stateless ids listed in
     * config/packages/csrf.yaml rely on the double-submit cookie written by the
     * csrf-protection Stimulus controller when a form is submitted, and the
     * picker uploads through fetch() instead.
     */
    public const CSRF_TOKEN_ID = 'library_media_upload';

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new AggregateRootIdTransformer(MediaId::class),
        );
    }

    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var list<MediaType> $types */
        $types = $options['types'];

        $attr = $options['attr'] ?? [];

        $attr = array_merge($attr, [
            'data-controller' => trim(($attr['data-controller'] ?? '') . ' media-picker'),
            'data-media-picker-search-path-value' => $this->urlGenerator->generate('library_media_search'),
            'data-media-picker-upload-path-value' => $this->urlGenerator->generate('library_media_ajax_upload'),
            'data-media-picker-multiple-value' => $options['multiple'] ? 'true' : 'false',
            'data-media-picker-types-value' => implode(',', array_map(static fn(MediaType $type): string => $type->value, $types)),
            'data-media-picker-accept-value' => $options['accept'],
            'data-media-picker-token-value' => $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue(),
        ]);

        $view->vars['attr'] = $attr;
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['selected'] = $this->selectedMedia($form->getData());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // the field holds a MediaId, never a compound object
            'data_class' => null,
            'multiple' => false,
            'types' => [],
            'accept' => '',
            'placeholder' => new TranslatableMessage('No hay ningún archivo seleccionado'),
        ]);

        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('types', 'array');
        $resolver->setAllowedTypes('accept', 'string');
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media_picker';
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function selectedMedia(mixed $data): array
    {
        if (null === $data || '' === $data || [] === $data) {
            return [];
        }

        $ids = is_array($data) ? $data : [$data];

        $selected = [];

        foreach ($ids as $id) {
            $media = $this->repository->find($id instanceof MediaId ? $id : new MediaId((string) $id));

            if (!$media instanceof Media) {
                continue;
            }

            $selected[] = [
                'id' => (string) $media->id(),
                'name' => $media->name(),
                'fileName' => $media->fileName(),
                'type' => $media->type()->value,
                'isImage' => $media->isImage(),
                'url' => $this->urlGenerator->generate('library_media_show', ['mediaId' => (string) $media->id()]),
            ];
        }

        return $selected;
    }
}
