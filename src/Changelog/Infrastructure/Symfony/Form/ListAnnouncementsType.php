<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Symfony\Form;

use App\Changelog\Application\Announcement\List\ListAnnouncementsRequest;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListAnnouncementsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => new TranslatableMessage('Buscar'),
                'required' => false,
            ])
            ->add('category', EnumType::class, [
                'class' => ChangelogCategory::class,
                'label' => new TranslatableMessage('Categoría'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las categorías'),
                'choice_label' => fn(ChangelogCategory $category): string => $category->name,
                'choice_translation_domain' => 'changelogCategory',
            ])
            ->add('status', EnumType::class, [
                'class' => AnnouncementStatus::class,
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => fn(AnnouncementStatus $status): string => $status->name,
                'choice_translation_domain' => 'announcementStatusType',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListAnnouncementsRequest::class,
        ]);
    }
}
