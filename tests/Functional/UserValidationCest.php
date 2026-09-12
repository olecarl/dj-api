<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserValidationCest
{
    public function testValidUserHasNoViolations(FunctionalTester $I): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('hashed-password')
        ;

        $violations = $I->grabService(ValidatorInterface::class)->validate($user);

        $I->assertCount(0, $violations);
    }

    public function testUserRequiresValidEmailAndPassword(FunctionalTester $I): void
    {
        $user = (new User())
            ->setEmail('not-an-email')
        ;

        $violations = $I->grabService(ValidatorInterface::class)->validate($user);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $I->assertContains('Enter a valid email address.', $messages);
        $I->assertContains('Password is required.', $messages);
    }

    public function testUserRejectsInvalidRoleNames(FunctionalTester $I): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setRoles(['administrator'])
            ->setPassword('hashed-password')
        ;

        $violations = $I->grabService(ValidatorInterface::class)->validate($user);

        $I->assertSame(1, $violations->count());
        $I->assertSame(
            'Roles must use the ROLE_ prefix and uppercase characters.',
            $violations->get(0)->getMessage(),
        );
    }

    public function testUserEmailMustBeUnique(FunctionalTester $I): void
    {
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $existingUser = (new User())
            ->setEmail('user@example.com')
            ->setPassword('hashed-password')
        ;
        $entityManager->persist($existingUser);
        $entityManager->flush();

        $duplicateUser = (new User())
            ->setEmail('user@example.com')
            ->setPassword('hashed-password')
        ;
        $violations = $I->grabService(ValidatorInterface::class)->validate($duplicateUser);

        $I->assertSame(1, $violations->count());
        $I->assertSame('This email address is already registered.', $violations->get(0)->getMessage());
    }

    public function testNormalizedDuplicateEmailIsRejected(FunctionalTester $I): void
    {
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist((new User())->setEmail('user@example.com')->setPassword('hashed-password'));
        $entityManager->flush();

        $duplicateUser = (new User())->setEmail(' USER@EXAMPLE.COM ')->setPassword('hashed-password');
        $violations = $I->grabService(ValidatorInterface::class)->validate($duplicateUser);

        $I->assertSame('This email address is already registered.', $violations->get(0)->getMessage());
    }

    public function testPersistedUserGetsTimestamps(FunctionalTester $I): void
    {
        $user = (new User())->setEmail('user@example.com')->setPassword('hashed-password');
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        $I->assertNotNull($user->getCreatedAt());
        $I->assertNotNull($user->getUpdatedAt());
        $I->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $I->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testUpdatingUserKeepsCreationTimestampAndUpdatesModificationTimestamp(FunctionalTester $I): void
    {
        $user = (new User())->setEmail('user@example.com')->setPassword('hashed-password');
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        $createdAt = $user->getCreatedAt();
        $updatedAt = $user->getUpdatedAt();
        $user->setEmail('updated@example.com');
        $entityManager->flush();

        $I->assertSame($createdAt, $user->getCreatedAt());
        $I->assertNotNull($user->getUpdatedAt());
        $I->assertNotSame($updatedAt, $user->getUpdatedAt());
    }

    public function testInvalidUserIsDetectedBeforeFlush(FunctionalTester $I): void
    {
        $user = (new User())->setEmail('not-an-email')->setPassword('hashed-password');
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);

        $I->assertTrue($entityManager->getUnitOfWork()->isScheduledForInsert($user));
        $I->assertNotSame(0, $I->grabService(ValidatorInterface::class)->validate($user)->count());
    }
}
