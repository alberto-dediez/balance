<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\DataMapper\GroupedEmissionFactorsDataMapper;
use App\CarbonFootprint\Infrastructure\Symfony\Validator\UniqueEmissionFactorYears;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class GroupedEmissionFactorsType extends AbstractType
{
    public function __construct(
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groups = $this->emissionFactorGroupRepository->findByFilter(new EmissionFactorGroupFilter(limit: 0));

        foreach ($groups as $group) {
            $builder->add(self::groupKey($group), CollectionType::class, [
                'label' => false,
                'entry_type' => $options['entry_type'],
                'entry_options' => $options['entry_options'],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
                'constraints' => [new UniqueEmissionFactorYears()],
            ]);
        }

        $builder->setAttribute('groups', $groups);
        $builder->setDataMapper(new GroupedEmissionFactorsDataMapper($groups));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['groups'] = $form->getConfig()->getAttribute('groups');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_options' => ['label' => false],
            'compound' => true,
        ]);

        $resolver->setRequired('entry_type');
        $resolver->setAllowedTypes('entry_type', 'string');
    }

    public static function groupKey(EmissionFactorGroup $group): string
    {
        return 'group_' . str_replace('-', '_', (string) $group->id());
    }
}
