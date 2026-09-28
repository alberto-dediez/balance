<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Application\Media\Share\ShareMediaRequest;
use App\Library\Application\Media\Share\ShareMediaService;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<mixed>
 */
class MediaPickerType extends AbstractType
{
    public const CSRF_TOKEN_ID = 'library_media_upload';

    public static function csrfTokenId(?MediaVisibility $visibility): string
    {
        if (!$visibility instanceof MediaVisibility || MediaVisibility::Account === $visibility) {
            return self::CSRF_TOKEN_ID;
        }

        return self::CSRF_TOKEN_ID . '_' . $visibility->value;
    }

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly ShareMediaService $shareMedia,
        private readonly LoggedUser $loggedUser,
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

        $visibility = $options['visibility'];

        if (!$visibility instanceof MediaVisibility) {
            return;
        }

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($visibility): void {
                $this->shareSubmittedMedia($event->getForm(), $event->getForm()->getData(), $visibility);
            },
        );
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function shareSubmittedMedia(FormInterface $form, mixed $data, MediaVisibility $visibility): void
    {
        if (null === $data || '' === $data || [] === $data) {
            return;
        }

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return;
        }

        foreach (is_array($data) ? $data : [$data] as $mediaId) {
            if (!$mediaId instanceof MediaId) {
                continue;
            }

            try {
                ($this->shareMedia)(new ShareMediaRequest($mediaId, $visibility), $user);
            } catch (MediaNotFoundException) {
            } catch (UnauthorizedActionException) {
                $form->addError(new FormError('No puedes usar ese archivo en este campo'));
            }
        }
    }

    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var list<MediaType> $types */
        $types = $options['types'];

        $visibility = $options['visibility'];

        $attr = $options['attr'] ?? [];

        $attr = array_merge($attr, [
            'data-controller' => trim(($attr['data-controller'] ?? '') . ' media-picker'),
            'data-media-picker-search-path-value' => $this->urlGenerator->generate('library_media_search'),
            'data-media-picker-upload-path-value' => $this->urlGenerator->generate('library_media_ajax_upload'),
            'data-media-picker-multiple-value' => $options['multiple'] ? 'true' : 'false',
            'data-media-picker-types-value' => implode(',', array_map(static fn(MediaType $type): string => $type->value, $types)),
            'data-media-picker-accept-value' => $options['accept'],
            'data-media-picker-visibility-value' => $visibility instanceof MediaVisibility ? $visibility->value : '',
            'data-media-picker-token-value' => $this->csrfTokenManager->getToken(self::csrfTokenId($visibility))->getValue(),
        ]);

        $view->vars['attr'] = $attr;
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['visibility'] = $visibility;
        $view->vars['selected'] = $this->selectedMedia($form->getData());
        $view->vars['preview'] = $options['preview'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'error_bubbling' => false,
            'multiple' => false,
            'types' => [],
            'accept' => '',
            'visibility' => null,
            'placeholder' => new TranslatableMessage('No hay ningún archivo seleccionado'),
            'preview' => 'card',
        ]);

        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('types', 'array');
        $resolver->setAllowedTypes('accept', 'string');
        $resolver->setAllowedTypes('visibility', ['null', MediaVisibility::class]);
        $resolver->setAllowedValues('preview', ['card', 'avatar']);
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
