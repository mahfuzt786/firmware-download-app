<?php

namespace App\DataFixtures;

use App\Entity\AdminUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Seeds a default admin account for form-based authentication.
 */
class AdminUserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(AdminUser::class)->findOneBy([
            'username' => 'admin',
        ]);

        $admin = $existing instanceof AdminUser ? $existing : new AdminUser();
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));

        $manager->persist($admin);
        $manager->flush();

        echo "Loaded default admin user: admin\n";
    }
}
