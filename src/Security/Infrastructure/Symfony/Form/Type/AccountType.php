<?php

namespace App\Security\Infrastructure\Symfony\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use App\Security\Domain\Model\Account\Account;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class' => Account::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                $qb = $er->createQueryBuilder('u');

                $qb
                    ->where(
                        $qb->expr()->orX(
                            $qb->expr()->isNull('u.removed'),
                            $qb->expr()->eq('u.removed', $qb->expr()->literal(false))
                        )
                    )
                ;

                return $qb;
            }
        ));
    }
    
    public function getParent()
    {
        return EntityType::class;
    }
}