<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@orus.com');
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123'));
        $admin->setIsVerified(true);

        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@orus.com');
        $user->setUsername('user');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'User123'));
        $user->setIsVerified(true);

        $manager->persist($user);

        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@orus.com");
            $user->setUsername("user{$i}");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->hasher->hashPassword($user, "User{$i}123"));
            $user->setIsVerified(true);

            $manager->persist($user);
        }


        $manager->flush();
    }
}
