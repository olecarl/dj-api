<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserProfileResourceCest
{
    public function testAuthenticatedUserCanReadOwnProfile(FunctionalTester $I): void
    {
        $user = $this->createUserWithProfile($I, 'user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);

        $I->assertSame((string) $user->getUserProfile()->getId(), $response['id']);
        $I->assertSame('Hello, world!', $response['about']);
        $I->assertSame('user@example.com', $response['user']['email']);
        $I->assertSame((string) $user->getId(), $response['user']['id']);
    }

    public function testAuthenticatedUserCannotReadAnotherUsersProfile(FunctionalTester $I): void
    {
        $user = $this->createUserWithProfile($I, 'user@example.com', 'correct horse battery staple', 'Hello, world!');
        $otherUser = $this->createUser($I, 'other@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($otherUser, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(403);
    }

    public function testAdministratorCanReadAnyProfile(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $user = $this->createUserWithProfile($I, 'user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => (string) $user->getUserProfile()->getId(),
            'about' => 'Hello, world!',
            'user' => ['id' => (string) $user->getId(), 'email' => 'user@example.com'],
        ]);
    }

    public function testAnonymousUserCannotReadProfile(FunctionalTester $I): void
    {
        $user = $this->createUserWithProfile($I, 'user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(401);
    }

    public function testProfileOfMissingUserIsNotFound(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/00000000-0000-4000-8000-000000000000/profile');

        $I->seeResponseCodeIs(404);
    }

    public function testUserWithoutProfileIsNotFound(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(404);
    }

    public function testProfileIriUsesCanonicalPath(FunctionalTester $I): void
    {
        $user = $this->createUserWithProfile($I, 'user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($user, 'api');
        $I->haveHttpHeader('Accept', 'application/ld+json');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);

        $I->assertSame('/users/'.$user->getId().'/profile', $response['@id']);
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(FunctionalTester $I, string $email, string $password, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword($I->grabService(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createUserWithProfile(FunctionalTester $I, string $email, string $password, string $about): User
    {
        $user = $this->createUser($I, $email, $password);
        $profile = (new UserProfile())->setUser($user)->setAbout($about);
        $user->setUserProfile($profile);

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($profile);
        $entityManager->flush();

        return $user;
    }
}
