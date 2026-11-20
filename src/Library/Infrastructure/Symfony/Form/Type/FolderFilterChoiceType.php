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

/**
 * @extends AbstractType<mixed>
 */
class FolderFilterChoiceType extends AbstractType
{
    public function __construct(private readonly CurrentAccountFolders $folders) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'folder_names' => function (Options $options): array {
                $names = [];

                foreach ($this->folders->all() as $folder) {
                    $names[(string) $folder->id()] = $folder->name();
                }

                return $names;
            },
            'choice_loader' => static function (Options $options): CallbackChoiceLoader {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];

                return new CallbackChoiceLoader(static fn(): array => [
                    ListMediaRequest::ALL_FOLDERS,
                    ...array_map('strval', array_keys($names)),
                ]);
            },
            'choice_label' => static function (Options $options): \Closure {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];

                return static function (?string $choice) use ($names): string|TranslatableMessage {
                    if (ListMediaRequest::ALL_FOLDERS === $choice) {
                        return new TranslatableMessage('Todas las carpetas');
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
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
