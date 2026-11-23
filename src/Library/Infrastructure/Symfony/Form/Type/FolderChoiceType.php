<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Domain\Model\Folder\FolderId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<mixed>
 */
class FolderChoiceType extends AbstractType
{
    public function __construct(private readonly CurrentAccountFolders $folders) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'folder_names' => fn(Options $options): array => $this->folders->paths(),
            'choice_loader' => static function (Options $options): CallbackChoiceLoader {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];

                return new CallbackChoiceLoader(static fn(): array => array_map(
                    static fn(string $id): FolderId => new FolderId($id),
                    array_map('strval', array_keys($names)),
                ));
            },
            'choice_value' => static fn(?FolderId $folderId): ?string => $folderId instanceof FolderId ? (string) $folderId : null,
            'choice_label' => static function (Options $options): \Closure {
                /** @var array<string, string> $names */
                $names = $options['folder_names'];

                return static fn(?FolderId $folderId): string => $folderId instanceof FolderId ? ($names[(string) $folderId] ?? '') : '';
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
