<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\List\ListMediaRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class FolderFilterChoiceType extends AbstractType
{
    public function __construct(
        private readonly CurrentAccountFolders $folders,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'folder_names' => fn(Options $options): array => $this->folders->paths(),
            'folders_with_subfolders' => fn(Options $options): array => array_values(array_map(
                static fn(array $branch): string => (string) $branch['folder']->id(),
                array_filter(
                    $this->folders->tree(),
                    static fn(array $branch): bool => [] !== $branch['subfolders'],
                ),
            )),
            'choice_loader' => static function (Options $options): CallbackChoiceLoader {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];

                /** @var list<string> $withSubfolders */
                $withSubfolders = $options['folders_with_subfolders'];

                return new CallbackChoiceLoader(static function () use ($names, $withSubfolders): array {
                    $choices = [ListMediaRequest::ALL_FOLDERS];

                    foreach (array_map('strval', array_keys($names)) as $id) {
                        $choices[] = $id;

                        if (in_array($id, $withSubfolders, true)) {
                            $choices[] = ListMediaRequest::WITH_SUBFOLDERS_PREFIX . $id;
                        }
                    }

                    return $choices;
                });
            },
            'choice_label' => function (Options $options): \Closure {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];
                $translator = $this->translator;

                return static function (?string $choice) use ($names, $translator): string {
                    if (ListMediaRequest::ALL_FOLDERS === $choice) {
                        return $translator->trans('Todas las carpetas');
                    }

                    if (null !== $choice && str_starts_with($choice, ListMediaRequest::WITH_SUBFOLDERS_PREFIX)) {
                        $id = substr($choice, strlen(ListMediaRequest::WITH_SUBFOLDERS_PREFIX));

                        return $translator->trans('{folder} / Todas las subcarpetas', ['{folder}' => $names[$id] ?? '']);
                    }

                    return null === $choice ? '' : ($names[$choice] ?? '');
                };
            },
            'choice_translation_domain' => false,
            'required' => false,
            'placeholder' => new TranslatableMessage('Sin carpeta'),
            'label' => new TranslatableMessage('Carpeta'),
        ]);

        $resolver->setAllowedTypes('folder_names', 'array');
        $resolver->setAllowedTypes('folders_with_subfolders', 'array');
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
