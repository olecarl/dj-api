<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateUserCommandCest
{
    public function testCommandCreatesUserWithNormalizedEmailRolesAndHashedPassword(UnitTester $I): void
    {
        $persistedUser = null;
        $entityManager = Stub::makeEmpty(EntityManagerInterface::class, [
            'persist' => Expected::once(static function (User $user) use (&$persistedUser): void {
                $persistedUser = $user;
            }),
            'flush' => Expected::once(),
            'wrapInTransaction' => Expected::once(static fn (callable $operation): mixed => $operation()),
        ]);

        $userRepository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(null)]);
        $passwordHasher = Stub::makeEmpty(UserPasswordHasherInterface::class, ['hashPassword' => Expected::once('hashed-password')]);
        $validator = Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]);

        $tester = $this->createTester($entityManager, $userRepository, $passwordHasher, $validator);
        $tester->setInputs(['long-enough-password', 'long-enough-password']);

        $status = $tester->execute([
            '--role' => ['ROLE_ADMIN', 'ROLE_ADMIN'],
            'email' => '  USER@EXAMPLE.COM  ',
        ]);

        $I->assertSame(Command::SUCCESS, $status);
        $I->assertSame('user@example.com', $persistedUser?->getEmail());
        $I->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $persistedUser?->getRoles());
        $I->assertSame('hashed-password', $persistedUser?->getPassword());
        $I->assertStringContainsString('was created', $tester->getDisplay());
    }

    public function testCommandRejectsNonInteractiveExecution(UnitTester $I): void
    {
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            Stub::makeEmpty(UserRepositoryInterface::class),
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            Stub::makeEmpty(ValidatorInterface::class),
        );

        $status = $tester->execute(['email' => 'user@example.com'], ['interactive' => false]);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('must be run interactively', $tester->getDisplay());
    }

    public function testCommandRejectsExistingEmail(UnitTester $I): void
    {
        $userRepository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(new User())]);
        $validator = Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]);
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            $userRepository,
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            $validator,
        );

        $status = $tester->execute(['email' => 'user@example.com']);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testCommandRejectsEmailLongerThanDatabaseLimit(UnitTester $I): void
    {
        $validator = Stub::makeEmpty(ValidatorInterface::class, [
            'validate' => Expected::once(new ConstraintViolationList([
                new ConstraintViolation(
                    'This value is too long.',
                    null,
                    [],
                    null,
                    '',
                    str_repeat('a', 181).'@example.com',
                ),
            ])),
        ]);
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            Stub::makeEmpty(UserRepositoryInterface::class),
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            $validator,
        );

        $status = $tester->execute(['email' => str_repeat('a', 181).'@example.com']);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('too long', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidRole(UnitTester $I): void
    {
        $userRepository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(null)]);
        $validator = Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]);
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            $userRepository,
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            $validator,
        );
        $tester->setInputs(['long-enough-password', 'long-enough-password']);

        $status = $tester->execute([
            '--role' => ['ROLE_ADMIN', 'invalid-role'],
            'email' => 'user@example.com',
        ]);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('Invalid role "invalid-role"', $tester->getDisplay());
    }

    public function testCommandReturnsFailureForDuplicateEmailDuringFlush(UnitTester $I): void
    {
        $entityManager = Stub::makeEmpty(EntityManagerInterface::class, [
            'wrapInTransaction' => Expected::once(static function (): never {
                throw Stub::makeEmpty(UniqueConstraintViolationException::class);
            }),
        ]);
        $userRepository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(null)]);
        $validator = Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]);
        $tester = $this->createTester(
            $entityManager,
            $userRepository,
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            $validator,
        );
        $tester->setInputs(['long-enough-password', 'long-enough-password']);

        $status = $tester->execute(['email' => 'user@example.com']);

        $I->assertSame(Command::FAILURE, $status);
        $I->assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testCommandRetriesAfterShortPassword(UnitTester $I): void
    {
        $entityManager = Stub::makeEmpty(EntityManagerInterface::class, [
            'persist' => Expected::once(),
            'flush' => Expected::once(),
            'wrapInTransaction' => Expected::once(static fn (callable $operation): mixed => $operation()),
        ]);
        $tester = $this->createTester(
            $entityManager,
            Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(null)]),
            Stub::makeEmpty(UserPasswordHasherInterface::class, ['hashPassword' => Expected::once('hashed-password')]),
            Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]),
        );
        $tester->setInputs(['short', 'long-enough-password', 'long-enough-password']);

        $status = $tester->execute(['email' => 'user@example.com']);

        $I->assertSame(Command::SUCCESS, $status);
        $I->assertStringContainsString('at least 12 characters', $tester->getDisplay());
    }

    public function testCommandRetriesAfterMismatchedPasswordConfirmation(UnitTester $I): void
    {
        $entityManager = Stub::makeEmpty(EntityManagerInterface::class, [
            'persist' => Expected::once(),
            'flush' => Expected::once(),
            'wrapInTransaction' => Expected::once(static fn (callable $operation): mixed => $operation()),
        ]);
        $tester = $this->createTester(
            $entityManager,
            Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once(null)]),
            Stub::makeEmpty(UserPasswordHasherInterface::class, ['hashPassword' => Expected::once('hashed-password')]),
            Stub::makeEmpty(ValidatorInterface::class, ['validate' => Expected::once(new ConstraintViolationList())]),
        );
        $tester->setInputs(['long-enough-password', 'different-password', 'long-enough-password']);

        $status = $tester->execute(['email' => 'user@example.com']);

        $I->assertSame(Command::SUCCESS, $status);
        $I->assertStringContainsString('do not match', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidEmail(UnitTester $I): void
    {
        $validator = Stub::makeEmpty(ValidatorInterface::class, [
            'validate' => Expected::once(new ConstraintViolationList([
                new ConstraintViolation('Enter a valid email address.', null, [], null, '', 'not-an-email'),
            ])),
        ]);
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            Stub::makeEmpty(UserRepositoryInterface::class),
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            $validator,
        );

        $status = $tester->execute(['email' => 'not-an-email']);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('valid email', $tester->getDisplay());
    }

    public function testCommandRejectsEmptyEmail(UnitTester $I): void
    {
        $tester = $this->createTester(
            Stub::makeEmpty(EntityManagerInterface::class),
            Stub::makeEmpty(UserRepositoryInterface::class),
            Stub::makeEmpty(UserPasswordHasherInterface::class),
            Stub::makeEmpty(ValidatorInterface::class),
        );

        $status = $tester->execute(['email' => '   ']);

        $I->assertSame(Command::INVALID, $status);
        $I->assertStringContainsString('cannot be empty', $tester->getDisplay());
    }

    private function createTester(
        EntityManagerInterface $entityManager,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): CommandTester {
        $command = new CreateUserCommand($entityManager, $userRepository, $passwordHasher, $validator);

        return new CommandTester($command);
    }
}
