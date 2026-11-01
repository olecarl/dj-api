<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Support;

use App\Entity\User;
use App\Entity\UserProfile;
use Codeception\Actor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Inherited Methods.
 *
 * @method void wantTo(string $text)
 * @method void wantToTest(string $text)
 * @method void execute(callable $callable)
 * @method void expectTo(string $prediction)
 * @method void expect(string $prediction)
 * @method void amGoingTo(string $argumentation)
 * @method void am(string $role)
 * @method void lookForwardTo(string $achieveValue)
 * @method void comment(string $description)
 * @method void pause(array $vars = [])
 */
final class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * @param list<string> $roles
     */
    public function haveUser(
        string $email,
        string $password = 'correct horse battery staple',
        array $roles = [],
    ): User {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword($this->grabService(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $entityManager = $this->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * @param list<string> $roles
     */
    public function haveUserWithProfile(
        string $email,
        string $password,
        string $about,
        array $roles = [],
    ): User {
        $user = $this->haveUser($email, $password, $roles);
        $profile = (new UserProfile())->setUser($user)->setAbout($about);
        $user->setUserProfile($profile);

        $entityManager = $this->grabService(EntityManagerInterface::class);
        $entityManager->persist($profile);
        $entityManager->flush();

        return $user;
    }
}
