<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardCest
{
    public function testUnauthenticatedVisitorIsRedirectedToLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/dashboard');

        $I->seeCurrentUrlEquals('/login');
        $I->see('Sign in');
    }

    public function testUserCanLoginAndSeeDashboard(FunctionalTester $I): void
    {
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->seeCurrentUrlEquals('/dashboard');
        $I->see('Signed in as user@example.com');
        $I->see('ROLE_USER');
    }

    public function testLoginReturnsUserToOriginallyRequestedDashboard(FunctionalTester $I): void
    {
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');

        $I->amOnPage('/dashboard');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->seeCurrentUrlEquals('/dashboard');
    }

    public function testInvalidCredentialsRedisplayLoginError(FunctionalTester $I): void
    {
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'wrong password',
        ]);

        $I->seeCurrentUrlEquals('/login');
        $I->see('Invalid email or password.');
    }

    public function testUserCanLogout(FunctionalTester $I): void
    {
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);
        $I->click('Log out');

        $I->seeCurrentUrlEquals('/login');
        $I->amOnPage('/dashboard');
        $I->seeCurrentUrlEquals('/login');
    }

    private function createUser(FunctionalTester $I, string $email, string $password): User
    {
        $user = (new User())->setEmail($email);
        $user->setPassword($I->grabService(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
