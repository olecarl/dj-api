<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class UserRepositoryCest
{
    public function testUpgradePasswordUpdatesAndPersistsUser(UnitTester $I): void
    {
        $entityManager = Stub::makeEmpty(EntityManagerInterface::class, [
            'persist' => Expected::once(),
            'flush' => Expected::once(),
            'getClassMetadata' => Expected::once(new ClassMetadata(User::class)),
        ]);
        $registry = Stub::makeEmpty(ManagerRegistry::class, ['getManagerForClass' => Expected::once($entityManager)]);

        $user = new User();
        $repository = new UserRepository($registry);

        $repository->upgradePassword($user, 'new-hashed-password');

        $I->assertSame('new-hashed-password', $user->getPassword());
    }

    public function testUpgradePasswordRejectsUnsupportedUser(UnitTester $I): void
    {
        $registry = Stub::makeEmpty(ManagerRegistry::class);
        $repository = new UserRepository($registry);
        $unsupportedUser = Stub::makeEmpty(PasswordAuthenticatedUserInterface::class);

        $I->expectThrowable(UnsupportedUserException::class, static function () use ($repository, $unsupportedUser): void {
            $repository->upgradePassword($unsupportedUser, 'new-hashed-password');
        });
    }
}
