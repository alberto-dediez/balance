<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Infrastructure\Symfony\Form\Type\FolderChoiceType;
use App\Library\Infrastructure\UI\Web\Media\Bulk\BulkMediaRequest;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulkMediaType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('folderId', FolderChoiceType::class, [
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-conditions-value' => json_encode(['move']),
                ],
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => BulkMediaRequest::class,
        ]);
    }
}
