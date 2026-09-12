<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\Support\UnitTester;

final class UserCest
{
    public function testNewUserHasNoIdentityOrPassword(UnitTester $I): void
    {
        $user = new User();

        $I->assertNull($user->getId());
        $I->assertNull($user->getEmail());
        $I->expectThrowable(\LogicException::class, $user->getUserIdentifier(...));
        $I->assertNull($user->getPassword());
        $I->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUserStoresEmailPasswordAndRoles(UnitTester $I): void
    {
        $user = new User();

        $I->assertSame($user, $user->setEmail('user@example.com'));
        $I->assertSame($user, $user->setPassword('hashed-password'));
        $I->assertSame($user, $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN']));

        $I->assertSame('user@example.com', $user->getEmail());
        $I->assertSame('user@example.com', $user->getUserIdentifier());
        $I->assertSame('hashed-password', $user->getPassword());
        $I->assertSame(['ROLE_ADMIN', 2 => 'ROLE_USER'], $user->getRoles());
    }

    public function testUserNormalizesEmail(UnitTester $I): void
    {
        $user = (new User())->setEmail('  USER@EXAMPLE.COM  ');

        $I->assertSame('user@example.com', $user->getEmail());
        $I->assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testSerializeReplacesPasswordWithCrc32cHash(UnitTester $I): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setPassword('hashed-password')
        ;

        $serialized = $user->__serialize();

        $I->assertSame(hash('crc32c', 'hashed-password'), $serialized["\0".User::class."\0password"]);
        $I->assertNotSame('hashed-password', $serialized["\0".User::class."\0password"]);
    }

    public function testEmptyRolesStillGrantDefaultUserRole(UnitTester $I): void
    {
        $user = (new User())->setRoles([]);

        $I->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testNullPasswordIsHashedAsEmptyStringDuringSerialization(UnitTester $I): void
    {
        $serialized = (new User())->__serialize();

        $I->assertSame(hash('crc32c', ''), $serialized["\0".User::class."\0password"]);
    }

    public function testUserIdentifierRejectsWhitespaceOnlyEmail(UnitTester $I): void
    {
        $user = (new User())->setEmail('   ');

        $I->expectThrowable(\LogicException::class, $user->getUserIdentifier(...));
    }
}
