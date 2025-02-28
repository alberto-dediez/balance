<?php

namespace App\Security\Infrastructure\Symfony\Type;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RoleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $types = Role::cases();

        $choices = [];

        foreach ($types as $type) {
            $choices[$type->value] = $type;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'roles'
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }
}