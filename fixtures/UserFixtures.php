<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public const ADMIN_USER = 'admin-user';

    public const NORMAL_USER = 'normal-user';

    public function load(ObjectManager $manager): void
    {
        $user = UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin);

        $manager->persist($user);

        $this->addReference(self::ADMIN_USER, $user);

        $user = UserMother::fromUsernameAndRole('employee@dediez.software', Role::User);

        $manager->persist($user);

        $this->addReference(self::NORMAL_USER, $user);

        $manager->flush();
    }
}
