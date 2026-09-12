<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthenticationCest
{
    public function testValidCredentialsReturnJwtAndAllowApiAccess(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $this->sendLogin($I, 'user@example.com', 'correct horse battery staple');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertIsString($response['token'] ?? null);
        $I->assertNotSame('', $response['token']);

        $I->haveHttpHeader('Authorization', 'Bearer '.$response['token']);
        $I->sendGet('/users/'.$user->getId());

        $I->seeResponseCodeIs(200);
    }

    public function testInvalidPasswordIsRejected(FunctionalTester $I): void
    {
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $this->sendLogin($I, 'user@example.com', 'wrong password');

        $I->seeResponseCodeIs(401);
    }

    public function testUnknownUserIsRejected(FunctionalTester $I): void
    {
        $this->sendLogin($I, 'unknown@example.com', 'correct horse battery staple');

        $I->seeResponseCodeIs(401);
    }

    public function testMissingCredentialsAreRejected(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/login_check', []);

        $I->seeResponseCodeIs(400);
    }

    private function sendLogin(FunctionalTester $I, string $email, string $password): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/login_check', ['username' => $email, 'password' => $password]);
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
